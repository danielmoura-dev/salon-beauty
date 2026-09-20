<?php

namespace Tests\Feature;

use App\Http\Controllers\WebhookController;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\Support\FakeMpWebhookController;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-21 10:00:00');
        config([
            'services.mercadopago.webhook_secret' => 'mp_secret',
            'services.mercadopago.access_token'   => 'APP_USR-test',
        ]);
        FakeMpWebhookController::$payment = null;
        FakeMpWebhookController::$apiDown = false;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sub(Tenant $tenant, string $gateway, string $status, ?string $periodEnd, array $extra = []): Subscription
    {
        return Subscription::create([
            'tenant_id' => $tenant->id, 'gateway' => $gateway, 'status' => $status,
            'gateway_subscription_id' => $extra['id'] ?? uniqid('gw_'), 'current_period_end' => $periodEnd,
        ]);
    }

    // ---------- Tenant::isActive ----------

    public function test_stripe_subscription_keeps_access(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $this->sub($tenant, 'stripe', 'active', '2026-10-21');

        $this->assertTrue($tenant->fresh()->isActive());
    }

    public function test_pix_access_lasts_until_the_end_of_the_paid_period_then_stops(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $sub    = $this->sub($tenant, 'mercadopago', 'active', '2026-09-21'); // vence hoje

        $this->assertTrue($tenant->fresh()->isActive(), 'no último dia pago ainda tem acesso');

        Carbon::setTestNow('2026-09-22 00:30:00');
        $this->assertFalse($tenant->fresh()->isActive(), 'depois do vencimento o PIX não renova sozinho');
    }

    public function test_expired_pix_blocks_writes_but_keeps_reads(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $this->sub($tenant, 'mercadopago', 'active', '2026-09-01');
        $this->actingAs($this->makeUser($tenant));

        $this->get('/clients')->assertOk();
        $this->postJson('/clients', ['name' => 'Fulana'])->assertStatus(402);
    }

    public function test_pending_renewal_qr_does_not_cut_access_before_the_period_ends(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $this->sub($tenant, 'mercadopago', 'pending', '2026-09-30');

        $this->assertTrue($tenant->fresh()->isActive());
    }

    public function test_never_paid_pix_with_active_flag_gives_no_access(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $this->sub($tenant, 'mercadopago', 'pending', null);

        $this->assertFalse($tenant->fresh()->isActive());
    }

    public function test_reused_pix_row_is_not_hidden_by_a_newer_cancelled_stripe_row(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $this->sub($tenant, 'mercadopago', 'active', '2026-08-01'); // pago e vencido
        $this->sub($tenant, 'stripe', 'cancelled', '2026-08-15');   // mais recente, cancelado

        $this->assertFalse($tenant->fresh()->isActive());
    }

    public function test_tenant_activated_manually_without_any_subscription_keeps_access(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);

        $this->assertTrue($tenant->fresh()->isActive());
    }

    public function test_trial_and_cancelled_behave_as_before(): void
    {
        $this->assertTrue($this->makeTenant(['plan_status' => 'trial', 'trial_ends_at' => now()->addDay()])->isActive());
        $this->assertFalse($this->makeTenant(['plan_status' => 'trial', 'trial_ends_at' => now()->subDay()])->isActive());
        $this->assertFalse($this->makeTenant(['plan_status' => 'cancelled'])->isActive());
    }

    // ---------- PIX: renovação antecipada ----------

    private function payPix(Tenant $tenant, string $paymentId): void
    {
        $this->app->bind(WebhookController::class, FakeMpWebhookController::class);
        FakeMpWebhookController::$payment = [
            'status' => 'approved', 'external_reference' => $tenant->id, 'transaction_amount' => 57.90,
        ];

        $reqId = 'req-' . $paymentId;
        $ts    = (string) time();
        $v1    = hash_hmac('sha256', "id:{$paymentId};request-id:{$reqId};ts:{$ts};", 'mp_secret');

        $this->call('POST', "/webhooks/mercadopago?data_id={$paymentId}", [], [], [], [
            'HTTP_X_REQUEST_ID' => $reqId, 'HTTP_X_SIGNATURE' => "ts={$ts},v1={$v1}", 'CONTENT_TYPE' => 'application/json',
        ], json_encode(['type' => 'payment', 'data' => ['id' => $paymentId]]))->assertOk();
    }

    public function test_first_pix_payment_grants_one_month_from_today(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'trial', 'trial_ends_at' => now()->addDays(3)]);

        $this->payPix($tenant, '9001');

        $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('2026-10-21', $sub->current_period_end->toDateString());
        $this->assertSame('active', $tenant->fresh()->plan_status);
    }

    public function test_paying_pix_before_expiry_extends_from_the_current_period_end(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $this->sub($tenant, 'mercadopago', 'pending', '2026-09-30', ['id' => 'old-qr']); // renovando com 9 dias sobrando

        $this->payPix($tenant, '9002');

        $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('2026-10-30', $sub->current_period_end->toDateString(), 'os dias restantes não podem ser perdidos');
        $this->assertSame('active', $sub->status);
    }

    public function test_paying_pix_after_it_expired_starts_a_new_month_from_today(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $this->sub($tenant, 'mercadopago', 'active', '2026-08-01');

        $this->payPix($tenant, '9003');

        $this->assertSame('2026-10-21', Subscription::where('tenant_id', $tenant->id)->first()->current_period_end->toDateString());
        $this->assertTrue($tenant->fresh()->isActive());
    }
}
