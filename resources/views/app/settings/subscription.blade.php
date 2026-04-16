@extends('layouts.app')
@section('title', 'Assinatura — Salon Beauty')

@section('content')
<div class="max-w-lg mx-auto space-y-6">

    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">Plano Full</h1>
        <p class="text-gray-500 mt-1">Acesso completo a todos os módulos do Salon Beauty</p>
    </div>

    {{-- ── Assinatura Stripe ATIVA ─────────────────────────────── --}}
    @if ($subscription?->gateway === 'stripe' && in_array($subscription->status, ['active', 'past_due']))
        <div class="rounded-2xl border-2 {{ $subscription->status === 'active' ? 'border-green-400 bg-green-50' : 'border-amber-400 bg-amber-50' }} p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Status da assinatura</p>
                    @if ($subscription->status === 'active')
                        <p class="text-lg font-bold text-green-700 flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                            Ativa — Cobrança automática semanal
                        </p>
                    @else
                        <p class="text-lg font-bold text-amber-700 flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                            Pagamento pendente
                        </p>
                    @endif
                </div>
                <svg class="h-8 w-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
            </div>

            @if ($subscription->current_period_end)
                <p class="text-sm text-gray-600">
                    Próxima cobrança em
                    <span class="font-semibold">{{ $subscription->current_period_end->format('d/m/Y') }}</span>
                </p>
            @endif

            <a href="{{ route('subscription.portal') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-white border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Gerenciar assinatura (trocar cartão, cancelar, faturas)
            </a>
        </div>

    {{-- ── Assinatura PIX ATIVA ─────────────────────────────────── --}}
    @elseif ($subscription?->gateway === 'mercadopago' && $subscription->status === 'active')
        <div class="rounded-2xl border-2 border-green-400 bg-green-50 p-6 space-y-3">
            <p class="text-lg font-bold text-green-700 flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                Ativa — PIX
            </p>
            @if ($subscription->current_period_end)
                <p class="text-sm text-gray-600">
                    Válida até
                    <span class="font-semibold">{{ $subscription->current_period_end->format('d/m/Y') }}</span>.
                    Para renovar, gere um novo QR code abaixo.
                </p>
            @endif
        </div>

        {{-- Permite gerar novo QR para renovar --}}
        <div x-data="pixPayment(null)" class="rounded-2xl border-2 border-gray-200 overflow-hidden">
            @include('app.settings._pix-section')
        </div>

    {{-- ── Assinatura cancelada / expirada / sem assinatura ────────── --}}
    @else
        {{-- Card do plano --}}
        <div class="rounded-2xl border-2 border-primary-500 bg-white shadow-sm p-6 text-center">
            <p class="text-4xl font-bold text-gray-900">R$ 57,90</p>
            <p class="text-gray-400 text-sm mt-1">por semana</p>

            <ul class="mt-5 space-y-2 text-sm text-left text-gray-600">
                @foreach ([
                    'Agenda com múltiplos profissionais',
                    'Comandas e PDV completo',
                    'Controle de clientes e comissões',
                    'Relatórios financeiros',
                    'Suporte ilimitado',
                ] as $feature)
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Opções de pagamento --}}
        <div class="space-y-3">
            <p class="text-sm font-semibold text-gray-700 text-center">Como deseja pagar?</p>

            {{-- PIX --}}
            <div x-data="pixPayment({{ json_encode($pixData) }})" class="rounded-2xl border-2 border-gray-200 overflow-hidden">
                @include('app.settings._pix-section')
            </div>

            {{-- Cartão de crédito (Stripe) --}}
            <form method="POST" action="{{ route('subscription.stripe') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-between rounded-2xl border-2 border-gray-200
                           px-5 py-4 hover:border-primary-400 hover:bg-primary-50 transition-colors group">
                    <div class="text-left">
                        <p class="font-semibold text-gray-900 group-hover:text-primary-700">Cartão de crédito</p>
                        <p class="text-xs text-gray-400">Cobrança automática semanal via Stripe</p>
                    </div>
                    <svg class="h-5 w-5 text-gray-300 group-hover:text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400">Cancele a qualquer momento. Sem fidelidade.</p>
    @endif

</div>
@endsection
