<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\BillingPortal\Session as StripePortalSession;

class SubscriptionController extends Controller
{
    // ── Página principal de assinatura ─────────────────────────────
    public function index()
    {
        $tenant       = auth()->user()->tenant->load('subscription');
        $subscription = $tenant->subscription;
        $pixData      = null;

        // Se há um pagamento PIX pendente, recupera o QR do MP para não perder ao navegar
        if ($subscription
            && $subscription->gateway === 'mercadopago'
            && $subscription->status === 'pending'
            && $subscription->gateway_subscription_id
        ) {
            try {
                MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
                $payment = (new PaymentClient())->get((int) $subscription->gateway_subscription_id);

                if (in_array($payment->status, ['pending', 'in_process'])) {
                    $pixData = [
                        'payment_id'     => $payment->id,
                        'qr_code'        => $payment->point_of_interaction->transaction_data->qr_code,
                        'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64,
                    ];
                }
            } catch (\Exception $e) {
                // Pagamento expirado — ignora, mostra botão de gerar novo
            }
        }

        return view('app.settings.subscription', compact('tenant', 'subscription', 'pixData'));
    }

    // ── Stripe: iniciar checkout ────────────────────────────────────
    public function checkoutStripe(Request $request)
    {
        $tenant = auth()->user()->tenant->load('subscription');

        // Já tem assinatura Stripe ativa → manda direto pro portal
        if ($tenant->subscription?->gateway === 'stripe'
            && in_array($tenant->subscription?->status, ['active', 'past_due'])
            && $tenant->stripe_customer_id
        ) {
            return $this->redirectToPortal($tenant->stripe_customer_id);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $params = [
            'mode'                  => 'subscription',
            'payment_method_types'  => ['card'],
            'line_items'            => [[
                'price'    => config('services.stripe.price_id'),
                'quantity' => 1,
            ]],
            'metadata'              => ['tenant_id' => $tenant->id],
            'success_url'           => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'            => route('subscription.index'),
            'allow_promotion_codes' => true,
        ];

        // Reutiliza customer Stripe existente para evitar duplicatas
        if ($tenant->stripe_customer_id) {
            $params['customer'] = $tenant->stripe_customer_id;
        }

        // Se tem PIX ativo com período futuro, Stripe só começa a cobrar após esse período
        // (o cliente não paga duas vezes pelo mesmo mês)
        $pixSub = $tenant->subscription;
        if ($pixSub?->gateway === 'mercadopago'
            && $pixSub->status === 'active'
            && $pixSub->current_period_end?->isFuture()
        ) {
            $params['subscription_data'] = [
                'trial_end' => $pixSub->current_period_end->timestamp,
            ];
        }

        // Se tem PIX pendente (QR não pago), cancela no MP antes de ir pro Stripe
        if ($pixSub?->gateway === 'mercadopago'
            && $pixSub->status === 'pending'
            && $pixSub->gateway_subscription_id
        ) {
            $this->cancelMpPayment((int) $pixSub->gateway_subscription_id);
            $pixSub->update(['status' => 'cancelled']);
        }

        $session = StripeSession::create($params);

        return redirect($session->url);
    }

    // ── Stripe: portal de gestão ────────────────────────────────────
    public function stripePortal()
    {
        $tenant = auth()->user()->tenant;

        if (! $tenant->stripe_customer_id) {
            return back()->with('error', 'Nenhuma assinatura Stripe encontrada.');
        }

        return $this->redirectToPortal($tenant->stripe_customer_id);
    }

    // ── Stripe: retorno após checkout ───────────────────────────────
    public function success(Request $request)
    {
        return view('app.settings.subscription-pending');
    }

    // ── Stripe: cancelar → portal (sem confirm() no browser) ───────
    public function cancelStripe(Request $request)
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->stripe_customer_id) {
            return $this->redirectToPortal($tenant->stripe_customer_id);
        }

        return back()->with('error', 'Nenhuma assinatura Stripe encontrada.');
    }

    // ── PIX via Mercado Pago ────────────────────────────────────────
    public function pixCheckout(Request $request)
    {
        $tenant = auth()->user()->tenant->load('subscription');

        // Não gera PIX se já tem Stripe ativo
        if ($tenant->subscription?->gateway === 'stripe'
            && in_array($tenant->subscription?->status, ['active', 'past_due'])
        ) {
            return response()->json(['error' => 'Você já tem uma assinatura ativa via cartão.'], 422);
        }

        // Reutiliza pagamento pendente existente (evita spam de QR codes)
        if ($tenant->subscription?->gateway === 'mercadopago'
            && $tenant->subscription->status === 'pending'
            && $tenant->subscription->gateway_subscription_id
        ) {
            try {
                MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
                $existing = (new PaymentClient())->get((int) $tenant->subscription->gateway_subscription_id);

                if (in_array($existing->status, ['pending', 'in_process'])) {
                    return response()->json([
                        'payment_id'     => $existing->id,
                        'qr_code'        => $existing->point_of_interaction->transaction_data->qr_code,
                        'qr_code_base64' => $existing->point_of_interaction->transaction_data->qr_code_base64,
                    ]);
                }
            } catch (\Exception $e) {
                // Pagamento expirado — cria um novo abaixo
            }
        }

        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        $payment = (new PaymentClient())->create([
            'transaction_amount' => (float) config('app.plan_price', 57.90),
            'description'        => 'Salon Beauty — Plano Mensal',
            'payment_method_id'  => 'pix',
            'payer'              => [
                // O Mercado Pago usa este email apenas para registro interno.
                // Para desabilitar o e-mail de confirmação enviado ao pagador,
                // acesse: MP Dashboard → Configurações → Notificações → desabilitar "Confirmação de pagamento".
                'email'      => $tenant->email,
                'first_name' => $tenant->name,
            ],
            'external_reference' => (string) $tenant->id,
            'notification_url'   => route('webhooks.mercadopago'),
            'date_of_expiration' => now()->addHours(24)->format('Y-m-d\TH:i:s.000P'),
        ]);

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id, 'gateway' => 'mercadopago'],
            [
                'gateway_subscription_id' => (string) $payment->id,
                'status'                  => 'pending',
                'current_period_end'      => null,
            ]
        );

        return response()->json([
            'payment_id'     => $payment->id,
            'qr_code'        => $payment->point_of_interaction->transaction_data->qr_code,
            'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64,
        ]);
    }

    // ── PIX: polling de status ──────────────────────────────────────
    public function pixStatus()
    {
        $subscription = auth()->user()->tenant->subscription;

        if ($subscription && $subscription->status === 'active') {
            return response()->json(['status' => 'active']);
        }

        return response()->json(['status' => 'pending']);
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function redirectToPortal(string $customerId)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripePortalSession::create([
            'customer'   => $customerId,
            'return_url' => route('subscription.index'),
        ]);

        return redirect($session->url);
    }

    private function cancelMpPayment(int $paymentId): void
    {
        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
            (new PaymentClient())->cancel($paymentId);
        } catch (\Exception $e) {
            // Ignora se já expirou ou foi pago — não é erro crítico
        }
    }
}
