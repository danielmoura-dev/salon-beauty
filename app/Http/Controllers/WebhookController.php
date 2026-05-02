<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook as StripeWebhook;

class WebhookController extends Controller
{
    // ============================================================
    // STRIPE WEBHOOK
    // ============================================================
    public function stripe(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = StripeWebhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: assinatura inválida');
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response('Webhook error', 400);
        }

        Log::info('Stripe event: ' . $event->type);

        match ($event->type) {
            'checkout.session.completed'      => $this->handleStripeCheckoutCompleted($event->data->object),
            'invoice.paid'                    => $this->handleStripeInvoicePaid($event->data->object),
            'invoice.payment_failed'          => $this->handleStripePaymentFailed($event->data->object),
            'customer.subscription.updated'   => $this->handleStripeSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted'   => $this->handleStripeSubscriptionDeleted($event->data->object),
            default                           => null,
        };

        return response('OK', 200);
    }

    private function handleStripeCheckoutCompleted(object $session): void
    {
        $tenantId = $session->metadata->tenant_id ?? null;
        if (! $tenantId) return;

        $tenant = Tenant::find($tenantId);
        if (! $tenant) return;

        // current_period_end será atualizado pelo invoice.paid que chega junto
        Subscription::updateOrCreate(
            ['tenant_id' => $tenantId, 'gateway' => 'stripe'],
            [
                'gateway_subscription_id' => $session->subscription,
                'gateway_customer_id'     => $session->customer,
                'status'                  => 'active',
                'current_period_end'      => now()->addMonth(),
            ]
        );

        // Cancela qualquer assinatura PIX ativa ou pendente — Stripe assumiu
        Subscription::where('tenant_id', $tenantId)
            ->where('gateway', 'mercadopago')
            ->whereIn('status', ['active', 'pending'])
            ->update(['status' => 'cancelled']);

        // Salva o customer ID no tenant para reutilizar em checkouts futuros
        $tenant->update([
            'plan_status'        => 'active',
            'stripe_customer_id' => $session->customer,
        ]);

        Log::info("Tenant {$tenantId} ativado via Stripe checkout. Período até: " .
            Carbon::createFromTimestamp($stripeSub->current_period_end)->toDateString());
    }

    private function handleStripeInvoicePaid(object $invoice): void
    {
        if (! $invoice->subscription) return;

        $sub = Subscription::where('gateway_subscription_id', $invoice->subscription)->first();
        if (! $sub) return;

        // Usa o period.end da linha da fatura — correto para qualquer intervalo (semanal, mensal, etc.)
        $periodEnd = Carbon::createFromTimestamp($invoice->lines->data[0]->period->end);

        $sub->update([
            'status'             => 'active',
            'current_period_end' => $periodEnd,
        ]);

        $tenant = $sub->tenant;
        $tenant->update(['plan_status' => 'active']);

        $this->recordAffiliateCommission(
            tenant: $tenant,
            paidAmount: $invoice->amount_paid / 100,
            gateway: 'stripe',
            gatewayPaymentId: $invoice->payment_intent ?? $invoice->id,
            period: $periodEnd->format('Y-m'),
        );

        Log::info("Renovação Stripe confirmada para tenant {$sub->tenant_id}. Período até: {$periodEnd->toDateString()}");
    }

    private function handleStripePaymentFailed(object $invoice): void
    {
        if (! $invoice->subscription) return;

        $sub = Subscription::where('gateway_subscription_id', $invoice->subscription)->first();
        if (! $sub) return;

        $sub->update(['status' => 'past_due']);
        $sub->tenant->update(['plan_status' => 'suspended']);

        Log::warning("Pagamento Stripe falhou para tenant {$sub->tenant_id}.");
    }

    private function handleStripeSubscriptionUpdated(object $stripeSub): void
    {
        $sub = Subscription::where('gateway_subscription_id', $stripeSub->id)->first();
        if (! $sub) return;

        $status = match ($stripeSub->status) {
            'active'   => 'active',
            'past_due' => 'past_due',
            'canceled' => 'cancelled',
            'paused'   => 'past_due',
            'unpaid'   => 'past_due',
            default    => $sub->status,
        };

        // Na API 2026-03-25 (billing_mode flexible), current_period_end
        // ficou em items.data[0].current_period_end, não mais no root
        $periodEnd = $stripeSub->items->data[0]->current_period_end
            ?? $stripeSub->current_period_end
            ?? null;

        // Cancelamento agendado: novo Stripe usa cancel_at (timestamp),
        // versões anteriores usavam cancel_at_period_end (boolean)
        $cancelAtPeriodEnd = (! empty($stripeSub->cancel_at))
            || (bool) ($stripeSub->cancel_at_period_end ?? false);

        $sub->update([
            'status'               => $status,
            'current_period_end'   => $periodEnd ? Carbon::createFromTimestamp($periodEnd) : $sub->current_period_end,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
        ]);

        $tenantStatus = match ($status) {
            'active'    => 'active',
            'cancelled' => 'cancelled',
            default     => 'suspended',
        };

        $sub->tenant->update(['plan_status' => $tenantStatus]);

        $cancelMsg = $cancelAtPeriodEnd ? ' (cancelamento agendado)' : '';
        Log::info("Stripe subscription updated para tenant {$sub->tenant_id}: {$status}{$cancelMsg}");
    }

    private function handleStripeSubscriptionDeleted(object $stripeSub): void
    {
        $sub = Subscription::where('gateway_subscription_id', $stripeSub->id)->first();
        if (! $sub) return;

        $sub->update(['status' => 'cancelled']);
        $sub->tenant->update([
            'plan_status'        => 'cancelled',
            'stripe_customer_id' => null,
        ]);

        Log::info("Assinatura Stripe cancelada para tenant {$sub->tenant_id}.");
    }

    // ============================================================
    // MERCADO PAGO WEBHOOK
    // ============================================================
    public function mercadoPago(Request $request)
    {
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');
        $dataId     = $request->query('data_id') ?? $request->input('data.id');

        if (! $this->validateMercadoPagoSignature($xSignature, $xRequestId, $dataId)) {
            Log::warning('Mercado Pago webhook: assinatura inválida');
            return response('Invalid signature', 400);
        }

        $type = $request->input('type') ?? $request->input('action');

        Log::info('MP webhook type: ' . $type);

        match ($type) {
            'payment'                => $this->handleMpPayment($dataId),
            'subscription_preapproval',
            'updated'               => $this->handleMpSubscription($dataId),
            default                 => null,
        };

        return response('OK', 200);
    }

    private function validateMercadoPagoSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId
    ): bool {
        if (! $xSignature) return false;

        $secret = config('services.mercadopago.webhook_secret');
        $parts  = [];

        foreach (explode(',', $xSignature) as $part) {
            [$k, $v] = explode('=', trim($part), 2);
            $parts[$k] = $v;
        }

        $ts      = $parts['ts']  ?? '';
        $hash    = $parts['v1']  ?? '';
        $message = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        return hash_equals(hash_hmac('sha256', $message, $secret), $hash);
    }

    private function handleMpPayment(string $paymentId): void
    {
        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

            $client  = new PaymentClient();
            $payment = $client->get((int) $paymentId);

            if ($payment->status !== 'approved') {
                Log::info("MP payment {$paymentId} status: {$payment->status} — ignorado.");
                return;
            }

            $tenantId = $payment->external_reference ?? null;
            if (! $tenantId) return;

            $tenant = Tenant::find($tenantId);
            if (! $tenant) return;

            // Se já tem Stripe ativo, ignora o PIX (pode ser QR antigo pago por engano)
            $hasActiveStripe = Subscription::where('tenant_id', $tenantId)
                ->where('gateway', 'stripe')
                ->where('status', 'active')
                ->exists();

            if ($hasActiveStripe) {
                Log::info("MP payment {$paymentId}: tenant {$tenantId} já tem Stripe ativo — ignorando PIX.");
                return;
            }

            Subscription::updateOrCreate(
                ['tenant_id' => $tenantId, 'gateway' => 'mercadopago'],
                [
                    'gateway_subscription_id' => $paymentId,
                    'status'                  => 'active',
                    'current_period_end'      => now()->addMonth(),
                ]
            );

            $tenant->update(['plan_status' => 'active']);

            $this->recordAffiliateCommission(
                tenant: $tenant,
                paidAmount: (float) $payment->transaction_amount,
                gateway: 'mercadopago',
                gatewayPaymentId: $paymentId,
                period: now()->format('Y-m'),
            );

            Log::info("PIX aprovado — tenant {$tenantId} ativo até " . now()->addMonth()->toDateString());

        } catch (\Exception $e) {
            Log::error('MP payment webhook error: ' . $e->getMessage());
        }
    }

    private function recordAffiliateCommission(
        Tenant $tenant,
        float $paidAmount,
        string $gateway,
        string $gatewayPaymentId,
        string $period,
    ): void {
        $tenant->loadMissing('affiliate');

        if (! $tenant->affiliate_id || ! $tenant->affiliate) return;

        $affiliate  = $tenant->affiliate;
        $planPrice  = (float) config('app.plan_price', 57.90);
        $discountApplied = $paidAmount < ($planPrice * 0.99);
        $discountAmount  = $discountApplied ? round($planPrice - $paidAmount, 2) : 0.00;
        $commissionAmount = round($paidAmount * ($affiliate->commission_pct / 100), 2);

        AffiliateCommission::create([
            'affiliate_id'        => $affiliate->id,
            'tenant_id'           => $tenant->id,
            'subscription_amount' => $planPrice,
            'discount_amount'     => $discountAmount,
            'charged_amount'      => $paidAmount,
            'commission_amount'   => $commissionAmount,
            'gateway'             => $gateway,
            'gateway_payment_id'  => $gatewayPaymentId,
            'period'              => $period,
            'status'              => 'pending',
        ]);

        if ($discountApplied && $tenant->affiliate_discount_months_remaining > 0) {
            $tenant->decrement('affiliate_discount_months_remaining');
        }

        Log::info("Comissão R$ {$commissionAmount} registrada para afiliado {$affiliate->code} (tenant {$tenant->id}).");
    }

    private function handleMpSubscription(string $preApprovalId): void
    {
        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

            $client      = new \MercadoPago\Client\PreApproval\PreApprovalClient();
            $preApproval = $client->get($preApprovalId);

            $tenantId = $preApproval->external_reference ?? null;
            if (! $tenantId) return;

            $tenant = Tenant::find($tenantId);
            if (! $tenant) return;

            $mpStatus = $preApproval->status;

            $internalStatus = match ($mpStatus) {
                'authorized' => 'active',
                'paused'     => 'past_due',
                'cancelled'  => 'cancelled',
                default      => 'trialing',
            };

            Subscription::updateOrCreate(
                ['tenant_id' => $tenantId, 'gateway' => 'mercadopago'],
                [
                    'gateway_subscription_id' => $preApprovalId,
                    'status'                  => $internalStatus,
                    'current_period_end'      => now()->addMonth(),
                ]
            );

            $tenantPlanStatus = $mpStatus === 'authorized' ? 'active' :
                                ($mpStatus === 'cancelled' ? 'cancelled' : 'suspended');

            $tenant->update(['plan_status' => $tenantPlanStatus]);

            Log::info("MP preapproval {$preApprovalId}: {$mpStatus} → tenant {$tenantId} → {$tenantPlanStatus}");

        } catch (\Exception $e) {
            Log::error('Erro ao processar MP webhook: ' . $e->getMessage());
        }
    }
}
