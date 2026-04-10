@extends('layouts.app')
@section('title', 'Dashboard — Gestão Beauty')

@section('content')
<div class="space-y-6">

    {{-- Saudação --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            Olá, {{ $user->name }} 👋
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $tenant->name }} · {{ now()->translatedFormat('l, d \d\e F') }}</p>
    </div>

    {{-- Cards de resumo (placeholders para o módulo de relatórios) --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Agendamentos hoje', 'value' => '—', 'icon' => '📅', 'color' => 'rose'],
            ['label' => 'Comandas abertas',  'value' => '—', 'icon' => '🧾', 'color' => 'amber'],
            ['label' => 'Faturamento hoje',  'value' => 'R$ —', 'icon' => '💰', 'color' => 'green'],
            ['label' => 'Clientes novos',    'value' => '—', 'icon' => '👥', 'color' => 'blue'],
        ] as $card)
            <div class="rounded-2xl bg-white border border-gray-100 p-4 shadow-sm">
                <div class="text-2xl mb-2">{{ $card['icon'] }}</div>
                <div class="text-xl font-bold text-gray-800">{{ $card['value'] }}</div>
                <div class="text-xs text-gray-400 mt-0.5 leading-tight">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Trial banner --}}
    @if ($tenant->plan_status === 'trial')
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 flex items-center gap-4">
            <span class="text-2xl">⏳</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-rose-700">Período de teste</p>
                <p class="text-xs text-rose-500">
                    Seu trial expira em {{ $tenant->trial_ends_at?->diffForHumans() }}.
                    Assine para não perder o acesso.
                </p>
            </div>
            <a href="{{ route('settings') }}"
               class="shrink-0 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700 transition-colors">
                Ver planos
            </a>
        </div>
    @endif

    {{-- Atalhos rápidos --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Acesso rápido</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ([
                ['route' => 'agenda',      'label' => 'Abrir Agenda',         'icon' => '📅'],
                ['route' => 'orders',      'label' => 'Nova Comanda',          'icon' => '🧾'],
                ['route' => 'clients',     'label' => 'Cadastrar Cliente',     'icon' => '👤'],
                ['route' => 'services',    'label' => 'Gerenciar Serviços',    'icon' => '✂'],
                ['route' => 'expenses',    'label' => 'Lançar Despesa',        'icon' => '💸'],
                ['route' => 'reports',     'label' => 'Ver Relatórios',        'icon' => '📊'],
            ] as $shortcut)
                <a href="{{ route($shortcut['route']) }}"
                   class="flex items-center gap-3 rounded-2xl bg-white border border-gray-100 px-4 py-3.5
                          shadow-sm hover:border-rose-200 hover:bg-rose-50 transition-colors group">
                    <span class="text-xl">{{ $shortcut['icon'] }}</span>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-rose-700">
                        {{ $shortcut['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection