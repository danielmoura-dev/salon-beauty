@extends('layouts.app')
@section('title', 'Dashboard — Gestão Beauty')

@section('content')
<div class="space-y-6">

    {{-- Saudação --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Olá, {{ $user->name }} 👋</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ $tenant->name }} · {{ now()->translatedFormat('l, d \d\e F') }}
        </p>
    </div>

    {{-- Cards métricas --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <div class="text-2xl mb-2">📅</div>
            <div class="text-2xl font-bold text-gray-900">{{ $appointmentsToday }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Agendamentos hoje</div>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <div class="text-2xl mb-2">🧾</div>
            <div class="text-2xl font-bold text-amber-600">{{ $openOrders }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Comandas abertas</div>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <div class="text-2xl mb-2">💰</div>
            <div class="text-xl font-bold text-green-700">
                R$ {{ number_format($revenueToday, 2, ',', '.') }}
            </div>
            <div class="text-xs text-gray-400 mt-0.5">Faturado hoje</div>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <div class="text-2xl mb-2">👥</div>
            <div class="text-2xl font-bold text-blue-600">{{ $newClientsMonth }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Novos no mês</div>
        </div>
    </div>

    {{-- Trial banner --}}
    @if ($tenant->plan_status === 'trial')
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 flex items-center gap-4">
            <span class="text-2xl shrink-0">⏳</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-rose-700">Período de teste</p>
                <p class="text-xs text-rose-500">
                    Expira {{ $tenant->trial_ends_at?->diffForHumans() }}.
                </p>
            </div>
            <a href="{{ route('settings') }}"
               class="shrink-0 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                Ver planos
            </a>
        </div>
    @endif

    {{-- Aniversariantes hoje --}}
    @if ($birthdays->isNotEmpty())
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4">
            <p class="text-sm font-semibold text-amber-700 mb-2">🎂 Aniversariantes hoje</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($birthdays as $client)
                    <span class="rounded-full bg-amber-100 text-amber-800 text-xs font-medium px-3 py-1">
                        {{ $client->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Próximos atendimentos do dia --}}
    @if ($nextAppointments->isNotEmpty())
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Próximos atendimentos</h2>
                <a href="{{ route('agenda') }}" class="text-sm text-rose-600 hover:underline">Ver agenda →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach ($nextAppointments as $apt)
                    @php $cfg = $apt->statusConfig(); @endphp
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="text-sm font-bold text-gray-900 w-12 shrink-0">
                            {{ substr($apt->start_time, 0, 5) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $apt->client->name }}</p>
                            <p class="text-xs text-gray-400">{{ $apt->professional->name }}</p>
                        </div>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $cfg['bg'] }} {{ $cfg['text'] }}">
                            {{ $cfg['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Atalhos rápidos --}}
    <div>
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Acesso rápido</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ([
                ['route' => 'agenda',       'label' => 'Abrir Agenda',       'icon' => '📅'],
                ['route' => 'orders',       'label' => 'Nova Comanda',        'icon' => '🧾'],
                ['route' => 'clients',      'label' => 'Clientes',           'icon' => '👤'],
                ['route' => 'expenses',     'label' => 'Lançar Despesa',     'icon' => '💸'],
                ['route' => 'reports',      'label' => 'Relatórios',         'icon' => '📊'],
                ['route' => 'professionals','label' => 'Profissionais',      'icon' => '💇'],
            ] as $s)
                <a href="{{ route($s['route']) }}"
                   class="flex items-center gap-3 rounded-2xl bg-white border border-gray-100 px-4 py-3.5
                          shadow-sm hover:border-rose-200 hover:bg-rose-50 transition-colors group">
                    <span class="text-xl">{{ $s['icon'] }}</span>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-rose-700">{{ $s['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection