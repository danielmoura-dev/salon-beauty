@extends('layouts.app')
@section('title', 'Relatórios — Gestão Beauty')

@section('content')
<div class="space-y-6">

    {{-- Cabeçalho + seletor de mês --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
            <p class="text-sm text-gray-400">{{ $month->translatedFormat('F \d\e Y') }}</p>
        </div>
        <form method="GET" class="sm:ml-auto flex items-center gap-2">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                   onchange="this.form.submit()"
                   class="rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
        </form>
    </div>

    {{-- Visão geral --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total vendido</p>
            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($revenueTotal, 2, ',', '.') }}</p>
            <div class="mt-3 space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Serviços</span>
                    <span class="font-medium text-rose-600">R$ {{ number_format($revenueServices, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Produtos</span>
                    <span class="font-medium text-blue-600">R$ {{ number_format($revenueProducts, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total de despesas</p>
            <p class="text-2xl font-bold text-red-600">R$ {{ number_format($expenseTotal, 2, ',', '.') }}</p>
            @if ($expenseByCategory->isNotEmpty())
                <div class="mt-3 space-y-1">
                    @foreach ($expenseByCategory->take(3) as $catName => $total)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 truncate">{{ $catName }}</span>
                            <span class="font-medium shrink-0 ml-2">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-2xl shadow-sm p-5 border
                    {{ $result >= 0
                        ? 'bg-green-50 border-green-200'
                        : 'bg-red-50 border-red-200' }}">
            <p class="text-xs uppercase tracking-wide mb-1
                      {{ $result >= 0 ? 'text-green-600' : 'text-red-500' }}">Resultado</p>
            <p class="text-2xl font-bold {{ $result >= 0 ? 'text-green-700' : 'text-red-700' }}">
                {{ $result >= 0 ? '+' : '' }}R$ {{ number_format($result, 2, ',', '.') }}
            </p>
            <p class="text-xs mt-2 {{ $result >= 0 ? 'text-green-600' : 'text-red-500' }}">
                {{ $result >= 0 ? 'Saldo positivo no mês' : 'Saldo negativo no mês' }}
            </p>
        </div>
    </div>

    {{-- Receita por método de pagamento --}}
    @if ($revenueByMethod->isNotEmpty())
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Receita por forma de pagamento</h2>
            <div class="space-y-3">
                @foreach ($revenueByMethod as $method => $total)
                    @php
                        $pct = $revenueTotal > 0 ? ($total / $revenueTotal) * 100 : 0;
                        $label = \App\Models\Payment::$methodLabels[$method] ?? $method;
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ $label }}</span>
                            <span class="font-semibold">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-rose-400 rounded-full transition-all"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Despesas por categoria --}}
    @if ($expenseByCategory->isNotEmpty())
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Despesas por categoria</h2>
            <div class="space-y-3">
                @foreach ($expenseByCategory as $catName => $total)
                    @php $pct = $expenseTotal > 0 ? ($total / $expenseTotal) * 100 : 0; @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ $catName }}</span>
                            <span class="font-semibold">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-red-400 rounded-full transition-all"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Aniversariantes --}}
    @if ($birthdays->isNotEmpty() || $upcomingBirthdays->isNotEmpty())
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-900 mb-4">🎂 Aniversariantes</h2>

            @if ($birthdays->isNotEmpty())
                <p class="text-xs font-semibold text-rose-500 uppercase tracking-wide mb-2">Hoje</p>
                <div class="space-y-2 mb-4">
                    @foreach ($birthdays as $client)
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-500 font-bold text-sm shrink-0">
                                {{ strtoupper(substr($client->name, 0, 1)) }}
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $client->name }}</span>
                            <span class="text-xs text-rose-500 ml-auto">🎉 Hoje!</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($upcomingBirthdays->isNotEmpty())
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Próximos 7 dias</p>
                <div class="space-y-2">
                    @foreach ($upcomingBirthdays as $client)
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-sm shrink-0">
                                {{ strtoupper(substr($client->name, 0, 1)) }}
                            </div>
                            <span class="text-sm text-gray-700">{{ $client->name }}</span>
                            <span class="text-xs text-gray-400 ml-auto">
                                {{ \Carbon\Carbon::createFromFormat('m-d', $client->birthday->format('m-d'))->format('d/m') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</div>
@endsection