@extends('layouts.app')
@section('title', 'Dashboard — Salon Beauty')

@section('content')
<div class="space-y-6">

    {{-- Saudação --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Olá, {{ $user->name }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ $tenant->name }} · {{ now()->translatedFormat('l, d \d\e F') }}
        </p>
    </div>

    {{-- Cards métricas --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <svg class="h-6 w-6 text-primary-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            <div class="text-2xl font-bold text-gray-900">{{ $appointmentsToday }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Agendamentos hoje</div>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <svg class="h-6 w-6 text-amber-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
            <div class="text-2xl font-bold text-amber-600">{{ $openOrders }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Comandas abertas</div>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <svg class="h-6 w-6 text-green-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="text-xl font-bold text-green-700">R$ {{ number_format($revenueToday, 2, ',', '.') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Faturado hoje</div>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
            <svg class="h-6 w-6 text-blue-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            <div class="text-2xl font-bold text-blue-600">{{ $newClientsMonth }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Novos no mês</div>
        </div>
    </div>

    {{-- Trial banner --}}
    @if ($tenant->plan_status === 'trial')
        <div class="rounded-2xl bg-primary-50 border border-primary-200 p-4 flex items-center gap-4">
            <svg class="h-6 w-6 text-primary-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-primary-700">Período de teste</p>
                <p class="text-xs text-primary-500">
                    Expira {{ $tenant->trial_ends_at?->diffForHumans() }}.
                </p>
            </div>
            <a href="{{ route('settings') }}"
               class="shrink-0 rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white hover:bg-primary-700">
                Ver planos
            </a>
        </div>
    @endif

    {{-- Aniversariantes hoje --}}
    @if ($birthdays->isNotEmpty())
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4">
            <p class="text-sm font-semibold text-amber-700 mb-2 flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-1.5-.75M3 16.5v-1.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 15v1.5"/></svg>
                Aniversariantes hoje
            </p>
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
                <a href="{{ route('agenda') }}" class="text-sm text-primary-600 hover:underline flex items-center gap-1">
                    Ver agenda
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
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
            @php
            $shortcuts = [
                ['route' => 'agenda',        'label' => 'Abrir Agenda',    'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>'],
                ['route' => 'orders',        'label' => 'Nova Comanda',    'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>'],
                ['route' => 'clients',       'label' => 'Clientes',        'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>'],
                ['route' => 'expenses',      'label' => 'Lançar Despesa',  'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>'],
                ['route' => 'reports',       'label' => 'Relatórios',      'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>'],
                ['route' => 'professionals', 'label' => 'Profissionais',   'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 11-5.196 3 3 3 0 015.196-3zm1.536-.887a2.165 2.165 0 001.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863l2.077-1.199m0-3.328a4.323 4.323 0 012.068-1.379l5.325-1.628a4.5 4.5 0 012.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0010.607 12m3.736 0l7.794 4.5-.802.215a4.5 4.5 0 01-2.48-.043l-5.326-1.629a4.324 4.324 0 01-2.068-1.379M14.343 12l-2.882 1.664"/>'],
            ];
            @endphp
            @foreach ($shortcuts as $s)
                <a href="{{ route($s['route']) }}"
                   class="flex items-center gap-3 rounded-2xl bg-white border border-gray-100 px-4 py-3.5
                          shadow-sm hover:border-primary-200 hover:bg-primary-50 transition-colors group">
                    <svg class="h-5 w-5 text-gray-400 group-hover:text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $s['svg'] !!}</svg>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700">{{ $s['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection