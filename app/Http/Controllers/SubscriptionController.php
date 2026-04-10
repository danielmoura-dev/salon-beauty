<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

class SubscriptionController extends Controller
{
    public function index()
    {
        $tenant       = auth()->user()->tenant->load('subscription');
        $subscription = $tenant->subscription;

        return view('app.settings.subscription', compact('tenant', 'subscription'));
    }

    // Redireciona para o Stripe Checkout (cartão recorrente)
    public function checkoutStripe(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeSession::create([
            'mode'                => 'subscription',
            'payment_method_types' => ['card'],
            'line_items'          => [[
                'price'    => config('services.stripe.price_id'),
                'quantity' => 1,
            ]],
            'metadata'            => ['tenant_id' => auth()->user()->tenant_id],
            'success_url'         => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'          => route('settings'),
        ]);

        return redirect($session->url);
    }

    // Redireciona para o Mercado Pago (Pix recorrente)
    public function checkoutMercadoPago(Request $request)
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        $client = new PreApprovalClient();

        $preApproval = $client->create([
            'reason'              => 'Gestão Beauty — Plano Full',
            'auto_recurring'      => [
                'frequency'       => 1,
                'frequency_type'  => 'months',
                'transaction_amount' => (float) env('APP_PLAN_PRICE', 57.90),
                'currency_id'     => 'BRL',
            ],
            'back_url'            => route('subscription.success'),
            'external_reference'  => auth()->user()->tenant_id,
            'status'              => 'pending',
        ]);

        return redirect($preApproval->init_point);
    }

    public function success(Request $request)
    {
        // O webhook vai confirmar — aqui só mostramos tela de aguardo
        return view('app.settings.subscription-pending');
    }

    // Cancelar assinatura Stripe
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