<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $secret    = config('services.stripe.webhook_secret');

        // Sem segredo configurado qualquer um consegue assinar um evento (HMAC com chave vazia).
        if (! is_string($secret) || $secret === '') {
            Log::error('Stripe webhook: STRIPE_WEBHOOK_SECRET não configurado — evento recusado.');
            return response('Webhook not configured', 400);
        }

        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $secret);
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

        $periodEnd = now()->addMonth();

        DB::transaction(function () use ($tenant, $tenantId, $session, $periodEnd) {
            // current_period_end será atualizado pelo invoice.paid que chega junto
            Subscription::updateOrCreate(
                ['tenant_id' => $tenantId, 'gateway' => 'stripe'],
                [
                    'gateway_subscription_id' => $session->subscription,
                    'gateway_customer_id'     => $session->customer,
                    'status'                  => 'active',
                    'current_period_end'      => $periodEnd,
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
        });

        Log::info("Tenant {$tenantId} ativado via Stripe checkout. Período até: {$periodEnd->toDateString()}");
    }

    private function handleStripeInvoicePaid(object $invoice): void
    {
        if (! $invoice->subscription) return;

        $sub = Subscription::where('gateway_subscription_id', $invoice->subscription)->first();
        if (! $sub) return;

        // Usa o period.end da linha da fatura — correto para qualquer intervalo (semanal, mensal, etc.)
        $periodEnd = Carbon::createFromTimestamp($invoice->lines->data[0]->period->end);

        $tenant = $sub->tenant;

        DB::transaction(function () use ($sub, $tenant, $periodEnd, $invoice) {
            $sub->update([
                'status'             => 'active',
                'current_period_end' => $periodEnd,
            ]);

            $tenant->update(['plan_status' => 'active']);

            $this->recordAffiliateCommission(
                tenant: $tenant,
                paidAmount: $invoice->amount_paid / 100,
                gateway: 'stripe',
                gatewayPaymentId: $invoice->payment_intent ?? $invoice->id,
                period: $periodEnd->format('Y-m'),
            );
        });

        Log::info("Renovação Stripe confirmada para tenant {$sub->tenant_id}. Período até: {$periodEnd->toDateString()}");
    }

    private function handleStripePaymentFailed(object $invoice): void
    {
        if (! $invoice->subscription) return;

        $sub = Subscription::where('gateway_subscription_id', $invoice->subscription)->first();
        if (! $sub) return;

        DB::transaction(function () use ($sub) {
            $sub->update(['status' => 'past_due']);
            $sub->tenant->update(['plan_status' => 'suspended']);
        });

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

        $tenantStatus = match ($status) {
            'active'    => 'active',
            'cancelled' => 'cancelled',
            default     => 'suspended',
        };

        DB::transaction(function () use ($sub, $status, $periodEnd, $cancelAtPeriodEnd, $tenantStatus) {
            $sub->update([
                'status'               => $status,
                'current_period_end'   => $periodEnd ? Carbon::createFromTimestamp($periodEnd) : $sub->current_period_end,
                'cancel_at_period_end' => $cancelAtPeriodEnd,
            ]);

            $sub->tenant->update(['plan_status' => $tenantStatus]);
        });

        $cancelMsg = $cancelAtPeriodEnd ? ' (cancelamento agendado)' : '';
        Log::info("Stripe subscription updated para tenant {$sub->tenant_id}: {$status}{$cancelMsg}");
    }

    private function handleStripeSubscriptionDeleted(object $stripeSub): void
    {
        $sub = Subscription::where('gateway_subscription_id', $stripeSub->id)->first();
        if (! $sub) return;

        DB::transaction(function () use ($sub) {
            $sub->update(['status' => 'cancelled']);
            $sub->tenant->update([
                'plan_status'        => 'cancelled',
                'stripe_customer_id' => null,
            ]);
        });

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

        if (! is_string($dataId) && ! is_int($dataId)) {
            return response('Missing data id', 400);
        }
        $dataId = (string) $dataId;

        if (! $this->validateMercadoPagoSignature($xSignature, $xRequestId, $dataId)) {
            Log::warning('Mercado Pago webhook: assinatura inválida');
            return response('Invalid signature', 400);
        }

        $type = $request->input('type') ?? $request->input('action');

        Log::info('MP webhook type: ' . $type);

        try {
            match ($type) {
                'payment'                => $this->handleMpPayment($dataId),
                'subscription_preapproval',
                'updated'               => $this->handleMpSubscription($dataId),
                default                 => null,
            };
        } catch (\Throwable $e) {
            // 5xx faz o Mercado Pago reenviar o evento; engolir o erro perderia o pagamento.
            Log::error('Mercado Pago webhook falhou: ' . $e->getMessage(), ['type' => $type, 'data_id' => $dataId]);
            return response('Processing error', 500);
        }

        return response('OK', 200);
    }

    private function validateMercadoPagoSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId
    ): bool {
        $secret = config('services.mercadopago.webhook_secret');

        // Sem segredo configurado qualquer um consegue assinar um evento (HMAC com chave vazia).
        if (! $xSignature || ! is_string($secret) || $secret === '') return false;

        $parts = [];

        foreach (explode(',', $xSignature) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) continue;
            $parts[$kv[0]] = $kv[1];
        }

        $ts      = $parts['ts']  ?? '';
        $hash    = $parts['v1']  ?? '';
        $message = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        return $hash !== '' && hash_equals(hash_hmac('sha256', $message, $secret), $hash);
    }

    /** Consulta o pagamento no Mercado Pago (extraído para poder ser substituído nos testes). */
    protected function fetchMpPayment(string $id): object
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        return (new PaymentClient())->get((int) $id);
    }

    /** Consulta a assinatura (preapproval) no Mercado Pago. */
    protected function fetchMpPreApproval(string $id): object
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        return (new \MercadoPago\Client\PreApproval\PreApprovalClient())->get($id);
    }

    private function handleMpPayment(string $paymentId): void
    {
        $payment = $this->fetchMpPayment($paymentId);

        if ($payment->status !== 'approved') {
            Log::info("MP payment {$paymentId} status: {$payment->status} — ignorado.");
            return;
        }

        $tenantId = $payment->external_reference ?? null;
        if (! $tenantId) return;

        $tenant = Tenant::find($tenantId);
        if (! $tenant) return;

        // Reentrega do mesmo pagamento: já processado, não estende o período nem duplica comissão
        $alreadyProcessed = Subscription::where('tenant_id', $tenantId)
            ->where('gateway', 'mercadopago')
            ->where('gateway_subscription_id', $paymentId)
            ->where('status', 'active')
            ->exists();

        if ($alreadyProcessed) {
            Log::info("MP payment {$paymentId}: já processado — ignorando reentrega.");
            return;
        }

        // Se já tem Stripe ativo, ignora o PIX (pode ser QR antigo pago por engano)
        $hasActiveStripe = Subscription::where('tenant_id', $tenantId)
            ->where('gateway', 'stripe')
            ->where('status', 'active')
            ->exists();

        if ($hasActiveStripe) {
            Log::info("MP payment {$paymentId}: tenant {$tenantId} já tem Stripe ativo — ignorando PIX.");
            return;
        }

        DB::transaction(function () use ($tenant, $tenantId, $paymentId, $payment) {
            // Renovação antecipada soma um mês ao vencimento atual (não perde os dias que sobravam);
            // se já venceu (ou é o primeiro pagamento), conta um mês a partir de hoje.
            $current = $tenant->subscriptionFor('mercadopago')?->current_period_end;
            $base    = $current && $current->gte(today()) ? $current->copy() : today();
            $newEnd  = $base->addMonthNoOverflow();

            Subscription::updateOrCreate(
                ['tenant_id' => $tenantId, 'gateway' => 'mercadopago'],
                [
                    'gateway_subscription_id' => $paymentId,
                    'status'                  => 'active',
                    'current_period_end'      => $newEnd,
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
        });

        Log::info("PIX aprovado — tenant {$tenantId} ativo até " . $tenant->subscriptionFor('mercadopago')?->current_period_end?->toDateString());
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

        // Idempotente: o gateway pode reenviar o mesmo evento
        $commission = AffiliateCommission::firstOrCreate(
            ['gateway' => $gateway, 'gateway_payment_id' => $gatewayPaymentId],
            [
                'affiliate_id'        => $affiliate->id,
                'tenant_id'           => $tenant->id,
                'subscription_amount' => $planPrice,
                'discount_amount'     => $discountAmount,
                'charged_amount'      => $paidAmount,
                'commission_amount'   => $commissionAmount,
                'period'              => $period,
                'status'              => 'pending',
            ]
        );

        if (! $commission->wasRecentlyCreated) {
            Log::info("Comissão do pagamento {$gatewayPaymentId} já registrada — ignorando reentrega.");
            return;
        }

        if ($discountApplied && $tenant->affiliate_discount_months_remaining > 0) {
            $tenant->decrement('affiliate_discount_months_remaining');
        }

        Log::info("Comissão R$ {$commissionAmount} registrada para afiliado {$affiliate->code} (tenant {$tenant->id}).");
    }

    private function handleMpSubscription(string $preApprovalId): void
    {
        $preApproval = $this->fetchMpPreApproval($preApprovalId);

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

        $tenantPlanStatus = $mpStatus === 'authorized' ? 'active' :
                            ($mpStatus === 'cancelled' ? 'cancelled' : 'suspended');

        DB::transaction(function () use ($tenant, $tenantId, $preApprovalId, $internalStatus, $tenantPlanStatus) {
            Subscription::updateOrCreate(
                ['tenant_id' => $tenantId, 'gateway' => 'mercadopago'],
                [
                    'gateway_subscription_id' => $preApprovalId,
                    'status'                  => $internalStatus,
                    'current_period_end'      => now()->addMonth(),
                ]
            );

            $tenant->update(['plan_status' => $tenantPlanStatus]);
        });

        Log::info("MP preapproval {$preApprovalId}: {$mpStatus} → tenant {$tenantId} → {$tenantPlanStatus}");
    }
}
