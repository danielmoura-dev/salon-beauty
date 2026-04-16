<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class SubscriptionController extends Controller
{
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
                $client  = new PaymentClient();
                $payment = $client->get((int) $subscription->gateway_subscription_id);

                if (in_array($payment->status, ['pending', 'in_process'])) {
                    $pixData = [
                        'payment_id'     => $payment->id,
                        'qr_code'        => $payment->point_of_interaction->transaction_data->qr_code,
                        'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64,
                    ];
                }
            } catch (\Exception $e) {
                // Pagamento expirado ou inválido — ignora, mostra botão de gerar novo
            }
        }

        return view('app.settings.subscription', compact('tenant', 'subscription', 'pixData'));
    }

    // ── Stripe (cartão recorrente) ──────────────────────────────────
    public function checkoutStripe(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeSession::create([
            'mode'                 => 'subscription',
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price'    => config('services.stripe.price_id'),
                'quantity' => 1,
            ]],
            'metadata'             => ['tenant_id' => auth()->user()->tenant_id],
            'success_url'          => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'           => route('settings'),
        ]);

        return redirect($session->url);
    }

    // ── PIX via Mercado Pago ────────────────────────────────────────
    public function pixCheckout(Request $request)
    {
        $tenant = auth()->user()->tenant;

        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        $client  = new PaymentClient();
        $payment = $client->create([
            'transaction_amount' => (float) config('app.plan_price', 57.90),
            'description'        => 'Salon Beauty — Plano Mensal',
            'payment_method_id'  => 'pix',
            'payer'              => [
                'email'      => $tenant->email,
                'first_name' => $tenant->name,
            ],
            'external_reference' => $tenant->id,
            'notification_url'   => route('webhooks.mercadopago'),
            'date_of_expiration' => now()->addHours(24)->format('Y-m-d\TH:i:s.000P'),
        ]);

        // Guarda pagamento pendente para rastrear via webhook
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

    // Polling — frontend verifica se o pagamento foi confirmado
    public function pixStatus()
    {
        $subscription = auth()->user()->tenant->subscription;

        if ($subscription && $subscription->status === 'active') {
            return response()->json(['status' => 'active']);
        }

        return response()->json(['status' => 'pending']);
    }

    public function success(Request $request)
    {
        return view('app.settings.subscription-pending');
    }

    // ── Cancelar Stripe ────────────────────────────────────────────
    public function cancelStripe(Request $request)
    {
        $subscription = auth()->user()->tenant->subscription;

        if (! $subscription || $subscription->gateway !== 'stripe') {
            return back()->with('error', 'Nenhuma assinatura Stripe ativa.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        \Stripe\Subscription::update($subscription->gateway_subscription_id, [
            'cancel_at_period_end' => true,
        ]);

        $subscription->update(['status' => 'cancelled']);

        return back()->with('success', 'Assinatura cancelada. Você tem acesso até o fim do período pago.');
    }
}
