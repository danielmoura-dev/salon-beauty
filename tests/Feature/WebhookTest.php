<?php

namespace Tests\Feature;

use App\Http\Controllers\WebhookController;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\Support\FakeMpWebhookController;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private const STRIPE_SECRET = 'whsec_test_secret';
    private const MP_SECRET     = 'mp_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.webhook_secret'      => self::STRIPE_SECRET,
            'services.mercadopago.webhook_secret' => self::MP_SECRET,
            'services.mercadopago.access_token'   => 'APP_USR-test',
        ]);

        FakeMpWebhookController::$payment     = null;
        FakeMpWebhookController::$preApproval = null;
        FakeMpWebhookController::$apiDown     = false;
    }

    // ---------- helpers ----------

    private function stripePost(string $type, array $object, ?string $secret = self::STRIPE_SECRET)
    {
        $payload = json_encode([
            'id'     => 'evt_' . uniqid(),
            'object' => 'event',
            'type'   => $type,
            'data'   => ['object' => $object],
        ]);

        $t   = time();
        $sig = hash_hmac('sha256', "{$t}.{$payload}", $secret);

        return $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$sig}",
            'CONTENT_TYPE'          => 'application/json',
        ], $payload);
    }

    private function mpPost(string $type, string $dataId, ?string $signatureHeader = null, bool $sign = true)
    {
        $reqId = 'req-' . uniqid();
        $ts    = (string) time();

        $headers = ['HTTP_X_REQUEST_ID' => $reqId, 'CONTENT_TYPE' => 'application/json'];

        if ($signatureHeader !== null) {
            $headers['HTTP_X_SIGNATURE'] = $signatureHeader;
        } elseif ($sign) {
            $v1 = hash_hmac('sha256', "id:{$dataId};request-id:{$reqId};ts:{$ts};", self::MP_SECRET);
            $headers['HTTP_X_SIGNATURE'] = "ts={$ts},v1={$v1}";
        }

        return $this->call('POST', '/webhooks/mercadopago?data_id=' . $dataId, [], [], [], $headers,
            json_encode(['type' => $type, 'data' => ['id' => $dataId]]));
    }

    private function affiliateTenant(): array
    {
        $affiliate = Affiliate::create([
            'name' => 'Afiliada', 'email' => 'af@example.com', 'code' => 'AFI10',
            'commission_pct' => 10, 'discount_pct' => 10,
        ]);
        $tenant = $this->makeTenant(['plan_status' => 'trial', 'affiliate_id' => $affiliate->id]);

        return [$tenant, $affiliate];
    }

    // ---------- Stripe ----------

    public function test_stripe_rejects_invalid_signature(): void
    {
        $this->stripePost('invoice.paid', ['id' => 'in_1'], 'whsec_errado')->assertStatus(400);
    }

    public function test_stripe_without_configured_secret_rejects_even_a_forged_empty_key_signature(): void
    {
        config(['services.stripe.webhook_secret' => '']);

        $this->stripePost('checkout.session.completed', [
            'id' => 'cs_x', 'object' => 'checkout.session',
            'metadata' => ['tenant_id' => 'x'], 'subscription' => 's', 'customer' => 'c',
        ], '')->assertStatus(400);
    }

    public function test_stripe_checkout_completed_activates_tenant_and_returns_200(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'trial']);

        $this->stripePost('checkout.session.completed', [
            'id' => 'cs_1', 'object' => 'checkout.session',
            'metadata'     => ['tenant_id' => $tenant->id],
            'subscription' => 'sub_1',
            'customer'     => 'cus_1',
        ])->assertOk();

        $tenant->refresh();
        $this->assertSame('active', $tenant->plan_status);
        $this->assertSame('cus_1', $tenant->stripe_customer_id);

        $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('stripe', $sub->gateway);
        $this->assertSame('sub_1', $sub->gateway_subscription_id);
        $this->assertSame('active', $sub->status);
    }

    public function test_stripe_invoice_paid_redelivery_does_not_duplicate_affiliate_commission(): void
    {
        [$tenant, $affiliate] = $this->affiliateTenant();
        Subscription::create([
            'tenant_id' => $tenant->id, 'gateway' => 'stripe',
            'gateway_subscription_id' => 'sub_1', 'status' => 'active',
        ]);
        $tenant->update(['affiliate_discount_months_remaining' => 3]);

        $invoice = [
            'id' => 'in_1', 'object' => 'invoice',
            'subscription'   => 'sub_1',
            'amount_paid'    => 5211, // com desconto de afiliado
            'payment_intent' => 'pi_1',
            'lines'          => ['data' => [['period' => ['end' => time() + 30 * 86400]]]],
        ];

        $this->stripePost('invoice.paid', $invoice)->assertOk();
        $this->stripePost('invoice.paid', $invoice)->assertOk(); // Stripe reenviou o evento

        $this->assertSame(1, AffiliateCommission::where('gateway_payment_id', 'pi_1')->count());
        $this->assertSame(2, $tenant->fresh()->affiliate_discount_months_remaining, 'desconto só pode ser consumido uma vez');
    }

    public function test_stripe_payment_failed_suspends_tenant(): void
    {
        $tenant = $this->makeTenant();
        Subscription::create(['tenant_id' => $tenant->id, 'gateway' => 'stripe', 'gateway_subscription_id' => 'sub_9', 'status' => 'active']);

        $this->stripePost('invoice.payment_failed', ['id' => 'in_9', 'object' => 'invoice', 'subscription' => 'sub_9'])->assertOk();

        $this->assertSame('suspended', $tenant->fresh()->plan_status);
    }

    // ---------- Mercado Pago ----------

    public function test_mp_rejects_missing_or_wrong_signature(): void
    {
        $this->mpPost('payment', '123', sign: false)->assertStatus(400);
        $this->mpPost('payment', '123', 'ts=1,v1=deadbeef')->assertStatus(400);
    }

    public function test_mp_malformed_signature_header_is_400_not_500(): void
    {
        $this->mpPost('payment', '123', 'lixo-sem-igual')->assertStatus(400);
    }

    public function test_mp_without_configured_secret_rejects_even_a_forged_empty_key_signature(): void
    {
        config(['services.mercadopago.webhook_secret' => null]);

        $reqId = 'req-1';
        $ts    = (string) time();
        $forged = hash_hmac('sha256', "id:123;request-id:{$reqId};ts:{$ts};", '');

        $this->call('POST', '/webhooks/mercadopago?data_id=123', [], [], [], [
            'HTTP_X_REQUEST_ID' => $reqId,
            'HTTP_X_SIGNATURE'  => "ts={$ts},v1={$forged}",
        ], json_encode(['type' => 'payment', 'data' => ['id' => '123']]))->assertStatus(400);
    }

    public function test_mp_missing_data_id_is_400(): void
    {
        $this->call('POST', '/webhooks/mercadopago', [], [], [], [
            'HTTP_X_SIGNATURE' => 'ts=1,v1=abc',
        ], json_encode(['type' => 'payment']))->assertStatus(400);
    }

    public function test_mp_approved_payment_activates_tenant_once_even_if_redelivered(): void
    {
        $this->app->bind(WebhookController::class, FakeMpWebhookController::class);
        [$tenant, $affiliate] = $this->affiliateTenant();

        FakeMpWebhookController::$payment = [
            'status' => 'approved', 'external_reference' => $tenant->id, 'transaction_amount' => 57.90,
        ];

        $this->mpPost('payment', '555')->assertOk();
        $this->mpPost('payment', '555')->assertOk();

        $this->assertSame('active', $tenant->fresh()->plan_status);
        $this->assertSame(1, Subscription::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, AffiliateCommission::where('gateway_payment_id', '555')->count());
    }

    public function test_mp_non_approved_payment_is_ignored(): void
    {
        $this->app->bind(WebhookController::class, FakeMpWebhookController::class);
        $tenant = $this->makeTenant(['plan_status' => 'trial']);

        FakeMpWebhookController::$payment = [
            'status' => 'pending', 'external_reference' => $tenant->id, 'transaction_amount' => 57.90,
        ];

        $this->mpPost('payment', '556')->assertOk();

        $this->assertSame('trial', $tenant->fresh()->plan_status);
        $this->assertSame(0, Subscription::where('tenant_id', $tenant->id)->count());
    }

    public function test_mp_api_failure_returns_5xx_so_gateway_retries(): void
    {
        $this->app->bind(WebhookController::class, FakeMpWebhookController::class);
        $tenant = $this->makeTenant(['plan_status' => 'trial']);
        FakeMpWebhookController::$apiDown = true;

        $this->mpPost('payment', '557')->assertStatus(500);

        $this->assertSame('trial', $tenant->fresh()->plan_status);
    }

    public function test_mp_pix_is_ignored_when_stripe_is_already_active(): void
    {
        $this->app->bind(WebhookController::class, FakeMpWebhookController::class);
        $tenant = $this->makeTenant();
        Subscription::create(['tenant_id' => $tenant->id, 'gateway' => 'stripe', 'gateway_subscription_id' => 'sub_1', 'status' => 'active']);

        FakeMpWebhookController::$payment = [
            'status' => 'approved', 'external_reference' => $tenant->id, 'transaction_amount' => 57.90,
        ];

        $this->mpPost('payment', '558')->assertOk();

        $this->assertSame(0, Subscription::where('tenant_id', $tenant->id)->where('gateway', 'mercadopago')->count());
    }
}
