@extends('layouts.app')
@section('title', 'Relatórios — Salon Beauty')

@section('content')
@php
    $profJson    = json_encode(array_values($byProfessional));
    $revTotal    = $revenueTotal;
    $recTotal    = $totalReceived;
    $commCalc    = $totalCommissionCalc;
    $commPaid    = $commissionsPaid;
    $vouchers    = $vouchersPeriod;
    $expenses    = $expenseTotal;
    $res1        = $result1;
    $res2        = $result2;
@endphp

<div class="space-y-5" x-data="reportPage()" x-init="init()">

    {{-- Cabeçalho + filtros --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex items-center gap-2">
            <svg class="h-6 w-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
            <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
        </div>

        <div class="sm:ml-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            {{-- Toggle Dia / Mês --}}
            <div class="flex rounded-xl border border-gray-200 overflow-hidden text-sm font-medium">
                <a href="{{ route('reports', ['mode' => 'day', 'date' => ($mode === 'day' ? $date->toDateString() : today()->toDateString())]) }}"
                   class="flex items-center gap-1.5 px-4 py-2 transition-colors {{ $mode === 'day' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    Dia
                </a>
                <a href="{{ route('reports', ['mode' => 'month', 'month' => ($mode === 'month' ? $month->format('Y-m') : now()->format('Y-m'))]) }}"
                   class="flex items-center gap-1.5 px-4 py-2 transition-colors {{ $mode === 'month' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/></svg>
                    Mês
                </a>
            </div>

            {{-- Navegação de data --}}
            @if ($mode === 'day')
                <div class="flex items-center gap-1">
                    <a href="{{ route('reports', ['mode' => 'day', 'date' => $date->copy()->subDay()->toDateString()]) }}"
                       class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    </a>
                    <form method="GET" action="{{ route('reports') }}" class="flex items-center">
                        <input type="hidden" name="mode" value="day">
                        <input type="date" name="date" value="{{ $date->toDateString() }}"
                               onchange="this.form.submit()"
                               class="rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500 py-1.5 px-3 cursor-pointer">
                    </form>
                    <a href="{{ route('reports', ['mode' => 'day', 'date' => $date->copy()->addDay()->toDateString()]) }}"
                       class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                </div>
            @else
                <div class="flex items-center gap-1">
                    <a href="{{ route('reports', ['mode' => 'month', 'month' => $month->copy()->subMonth()->format('Y-m')]) }}"
                       class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    </a>
                    <span class="px-3 text-sm font-semibold text-gray-800 min-w-[130px] text-center capitalize">
                        {{ $month->translatedFormat('F Y') }}
                    </span>
                    <a href="{{ route('reports', ['mode' => 'month', 'month' => $month->copy()->addMonth()->format('Y-m')]) }}"
                       class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Barra de visões + toggle vendas/recebimentos --}}
    <div class="flex flex-col sm:flex-row gap-2">
        {{-- Visões gerais --}}
        <div class="flex rounded-xl border border-gray-200 overflow-hidden text-sm font-medium">
            <button @click="overview = 'sales'"
                :class="overview === 'sales' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="flex items-center gap-1.5 px-4 py-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                Vendas
            </button>
            <button @click="overview = 'g1'"
                :class="overview === 'g1' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="flex items-center gap-1.5 px-4 py-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                Visão Geral 1
            </button>
            <button @click="overview = 'g2'"
                :class="overview === 'g2' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="flex items-center gap-1.5 px-4 py-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
                Visão Geral 2
            </button>
        </div>

        {{-- Toggle vendas / recebimentos --}}
        <button @click="saleMode = saleMode === 'sales' ? 'receipts' : 'sales'"
            class="sm:ml-auto flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
            <svg class="h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 3M21 7.5H7.5"/>
            </svg>
            <span x-text="saleMode === 'sales' ? 'Mostrando: Vendas' : 'Mostrando: Recebimentos'"></span>
        </button>
    </div>

    {{-- ===== VISÃO GERAL 1 ===== --}}
    <div x-show="overview === 'g1'" x-cloak>

        {{-- Tabela de profissionais --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-3">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100">
                <svg class="h-5 w-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                <h2 class="font-semibold text-gray-900">Vendas e comissões por profissional</h2>
            </div>

            <template x-if="professionals.length > 0">
                <div>
                    {{-- Header --}}
                    <div class="grid grid-cols-3 px-5 py-2 bg-gray-50 text-xs font-semibold text-gray-400 uppercase tracking-wide">
                        <span>Profissional</span>
                        <span class="text-right">Vendas</span>
                        <span class="text-right">Comissão</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <template x-for="prof in professionals" :key="prof.name">
                            <div class="grid grid-cols-3 items-center px-5 py-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="h-7 w-7 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                                        <span class="text-xs font-bold text-violet-500" x-text="prof.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-800 truncate" x-text="prof.name"></span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 text-right" x-text="fmt(prof.total)"></span>
                                <span class="text-sm font-semibold text-red-500 text-right" x-text="prof.commission > 0 ? '- ' + fmt(prof.commission) : '—'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="professionals.length === 0">
                <p class="px-5 py-4 text-sm text-gray-400">Nenhuma venda no período</p>
            </template>
        </div>

        {{-- Resumo Visão 1 --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2 mb-1">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                Resumo
            </h2>

            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="h-4 w-4 text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
                    <span x-text="saleMode === 'sales' ? 'Total de vendas' : 'Total recebido'"></span>
                </div>
                <span class="font-semibold text-gray-900" x-text="saleMode === 'sales' ? fmt({{ $revTotal }}) : fmt({{ $recTotal }})"></span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    Comissões calculadas
                </div>
                <span class="font-semibold text-red-500">- {{ 'R$ ' . number_format($commCalc, 2, ',', '.') }}</span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    Total de despesas
                </div>
                <span class="font-semibold text-red-500">- {{ 'R$ ' . number_format($expenses, 2, ',', '.') }}</span>
            </div>

            @php $res1Color = $res1 >= 0 ? 'text-green-600' : 'text-red-600'; @endphp
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <svg class="h-4 w-4 {{ $res1 >= 0 ? 'text-green-500' : 'text-red-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        @if ($res1 >= 0)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.511l-5.511-3.182"/>
                        @endif
                    </svg>
                    Resultado
                </div>
                <span class="text-lg font-bold {{ $res1Color }}">
                    {{ ($res1 >= 0 ? '+' : '') . 'R$ ' . number_format($res1, 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    {{-- ===== VISÃO GERAL 2 ===== --}}
    <div x-show="overview === 'g2'" x-cloak>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2 mb-1">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                Resumo financeiro
            </h2>

            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="h-4 w-4 text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
                    <span x-text="saleMode === 'sales' ? 'Total vendido' : 'Total recebido'"></span>
                </div>
                <span class="font-semibold text-gray-900" x-text="saleMode === 'sales' ? fmt({{ $revTotal }}) : fmt({{ $recTotal }})"></span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    Comissões pagas
                </div>
                <span class="font-semibold text-red-500">- {{ 'R$ ' . number_format($commPaid, 2, ',', '.') }}</span>
            </div>

            @if ($vouchers > 0)
            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75a3.75 3.75 0 01-7.5 0V6m-1.5 6.75h10.5a.75.75 0 00.75-.75V4.5a.75.75 0 00-.75-.75H4.5a.75.75 0 00-.75.75V12c0 .414.336.75.75.75H7.5m9 5.25h-9m9 0a2.25 2.25 0 002.25-2.25V13.5A2.25 2.25 0 0016.5 11.25H7.5A2.25 2.25 0 005.25 13.5v5.25a2.25 2.25 0 002.25 2.25h9z"/></svg>
                    Vales emitidos
                </div>
                <span class="font-semibold text-amber-500">- {{ 'R$ ' . number_format($vouchers, 2, ',', '.') }}</span>
            </div>
            @endif

            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    Despesas
                </div>
                <span class="font-semibold text-red-500">- {{ 'R$ ' . number_format($expenses, 2, ',', '.') }}</span>
            </div>

            @php $res2Color = $res2 >= 0 ? 'text-green-600' : 'text-red-600'; @endphp
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <svg class="h-4 w-4 {{ $res2 >= 0 ? 'text-green-500' : 'text-red-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        @if ($res2 >= 0)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.511l-5.511-3.182"/>
                        @endif
                    </svg>
                    Resultado
                </div>
                <span class="text-lg font-bold {{ $res2Color }}">
                    {{ ($res2 >= 0 ? '+' : '') . 'R$ ' . number_format($res2, 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    {{-- ===== SEÇÕES DE DETALHE (só visíveis na aba Vendas) ===== --}}
    <div x-show="overview === 'sales'" x-cloak class="space-y-5">

    {{-- Vendas por item --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
            Vendas por item
        </h2>
        @php
            $itemRows = [
                ['label' => 'Serviços', 'value' => $revenueServices, 'color' => 'bg-primary-400', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>', 'ic' => 'text-primary-500 bg-primary-50'],
                ['label' => 'Produtos',  'value' => $revenueProducts, 'color' => 'bg-blue-400',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>',                                                                                                                                                                                                              'ic' => 'text-blue-500 bg-blue-50'],
                ['label' => 'Outros',    'value' => $revenueOthers,   'color' => 'bg-amber-400',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'ic' => 'text-amber-500 bg-amber-50'],
            ];
        @endphp
        <div class="space-y-3">
            @foreach ($itemRows as $row)
                @php $pct = $revenueTotal > 0 ? ($row['value'] / $revenueTotal) * 100 : 0; @endphp
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="h-6 w-6 rounded-lg {{ $row['ic'] }} flex items-center justify-center shrink-0">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $row['icon'] !!}</svg>
                        </div>
                        <span class="text-sm text-gray-600 flex-1">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold">R$ {{ number_format($row['value'], 2, ',', '.') }}</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full {{ $row['color'] }} rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Vendas por pagamento --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
            Vendas por pagamento
        </h2>
        @php
            $methodIcons = [
                'pix'         => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>',                                                                                                                                                                                                              'ic' => 'text-teal-500 bg-teal-50'],
                'credit_card' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>', 'ic' => 'text-violet-500 bg-violet-50'],
                'debit_card'  => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>', 'ic' => 'text-blue-500 bg-blue-50'],
                'cash'        => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>', 'ic' => 'text-green-500 bg-green-50'],
                'credit'      => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',                              'ic' => 'text-amber-500 bg-amber-50'],
                'debt'        => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>',                                                                                  'ic' => 'text-red-500 bg-red-50'],
            ];
        @endphp
        @if ($revenueByMethod->isNotEmpty())
            <div class="space-y-3">
                @foreach ($revenueByMethod as $method => $total)
                    @php
                        $base = $saleMode ?? 'sales';
                        $pct  = $revenueTotal > 0 ? ($total / $revenueTotal) * 100 : 0;
                        $label = \App\Models\Payment::$methodLabels[$method] ?? $method;
                        $mi    = $methodIcons[$method] ?? ['icon' => '', 'ic' => 'text-gray-500 bg-gray-50'];
                    @endphp
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <div class="h-6 w-6 rounded-lg {{ $mi['ic'] }} flex items-center justify-center shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $mi['icon'] !!}</svg>
                            </div>
                            <span class="text-sm text-gray-600 flex-1">{{ $label }}</span>
                            <span class="text-sm font-semibold">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-green-400 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">Nenhuma venda no período</p>
        @endif
    </div>

    {{-- Vendas por profissional --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            Vendas por profissional
        </h2>
        @if (count($byProfessional) > 0)
            <div class="space-y-3">
                @foreach ($byProfessional as $row)
                    @php $pct = $revenueTotal > 0 ? ($row['total'] / $revenueTotal) * 100 : 0; @endphp
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <div class="h-6 w-6 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold text-violet-500">{{ strtoupper(substr($row['name'], 0, 1)) }}</span>
                            </div>
                            <span class="text-sm text-gray-600 flex-1">{{ $row['name'] }}</span>
                            <span class="text-sm font-semibold">R$ {{ number_format($row['total'], 2, ',', '.') }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-400 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">Nenhuma venda no período</p>
        @endif
    </div>

    {{-- Despesas --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            Despesas
        </h2>
        @if ($expenseByCategory->isNotEmpty())
            <div class="space-y-3">
                @foreach ($expenseByCategory as $catName => $total)
                    @php $pct = $expenseTotal > 0 ? ($total / $expenseTotal) * 100 : 0; @endphp
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <div class="h-6 w-6 rounded-lg text-red-400 bg-red-50 flex items-center justify-center shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776"/></svg>
                            </div>
                            <span class="text-sm text-gray-600 flex-1">{{ $catName }}</span>
                            <span class="text-sm font-semibold">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-red-400 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">Nenhuma despesa no período</p>
        @endif
    </div>

    </div>{{-- /seções de detalhe --}}

    {{-- Aniversariantes --}}
    @if ($birthdays->isNotEmpty() || $upcomingBirthdays->isNotEmpty())
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-1.5-.75M3 16.5v-1.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 15v1.5"/></svg>
                Aniversariantes
            </h2>
            @if ($birthdays->isNotEmpty())
                <p class="text-xs font-semibold text-primary-500 uppercase tracking-wide mb-2">Hoje</p>
                <div class="space-y-2 mb-4">
                    @foreach ($birthdays as $client)
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold text-sm shrink-0">{{ strtoupper(substr($client->name, 0, 1)) }}</div>
                            <span class="text-sm font-medium text-gray-900">{{ $client->name }}</span>
                            <span class="text-xs text-primary-500 ml-auto flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                Hoje!
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
            @if ($upcomingBirthdays->isNotEmpty())
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Próximos 7 dias</p>
                <div class="space-y-2">
                    @foreach ($upcomingBirthdays as $client)
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-sm shrink-0">{{ strtoupper(substr($client->name, 0, 1)) }}</div>
                            <span class="text-sm text-gray-700">{{ $client->name }}</span>
                            <span class="text-xs text-gray-400 ml-auto">{{ \Carbon\Carbon::createFromFormat('m-d', $client->birthday->format('m-d'))->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</div>

<script>
function reportPage() {
    return {
        overview: 'sales',
        saleMode: 'sales',
        professionals: [],

        init() {
            this.professionals = {!! $profJson !!};
        },

        fmt(value) {
            return 'R$ ' + Number(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}
</script>
@endsection
