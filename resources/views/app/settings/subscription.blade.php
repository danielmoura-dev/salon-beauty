@extends('layouts.app')
@section('title', 'Assinar — Gestão Beauty')

@section('content')
<div class="max-w-lg mx-auto space-y-6">

    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">Plano Full</h1>
        <p class="text-gray-500 mt-1">Acesso completo a todos os módulos do Gestão Beauty</p>
    </div>

    {{-- Card do plano --}}
    <div class="rounded-2xl border-2 border-primary-500 bg-white shadow-sm p-6 text-center">
        <p class="text-4xl font-bold text-gray-900">R$ 57,90</p>
        <p class="text-gray-400 text-sm mt-1">por mês</p>

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

        {{-- Stripe --}}
        <form method="POST" action="{{ route('subscription.stripe') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center justify-between rounded-2xl border-2 border-gray-200
                       px-5 py-4 hover:border-primary-400 hover:bg-primary-50 transition-colors group">
                <div class="text-left">
                    <p class="font-semibold text-gray-900 group-hover:text-primary-700">Cartão de crédito</p>
                    <p class="text-xs text-gray-400">Cobrança automática mensal via Stripe</p>
                </div>
                <svg class="h-5 w-5 text-gray-300 group-hover:text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </button>
        </form>

        {{-- Mercado Pago --}}
        <form method="POST" action="{{ route('subscription.mercadopago') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center justify-between rounded-2xl border-2 border-gray-200
                       px-5 py-4 hover:border-primary-400 hover:bg-primary-50 transition-colors group">
                <div class="text-left">
                    <p class="font-semibold text-gray-900 group-hover:text-primary-700">Pix recorrente</p>
                    <p class="text-xs text-gray-400">Autorize via Mercado Pago, pague com Pix</p>
                </div>
                <svg class="h-5 w-5 text-gray-300 group-hover:text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400">
        Cancele a qualquer momento. Sem fidelidade.
    </p>
</div>
@endsection