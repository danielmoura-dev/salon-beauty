@extends('layouts.app')
@section('title', 'Agenda — Salon Beauty')

@section('content')
<div x-data="agenda()" class="-m-4 sm:-m-6 lg:-m-8">

    {{-- ===== HEADER DA AGENDA ===== --}}
    <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-100">

        {{-- Navegação de datas --}}
        <a href="{{ route('agenda', ['date' => $date->copy()->subDay()->toDateString()]) }}"
           class="rounded-xl p-2 hover:bg-gray-100 transition-colors text-gray-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>

        <div class="flex-1 text-center">
            <h2 class="font-bold text-gray-900 text-base leading-tight">
                {{ $date->translatedFormat('l, d \d\e F') }}
            </h2>
            @if ($date->isToday())
                <span class="text-xs text-primary-500 font-medium">Hoje</span>
            @endif
        </div>

        <a href="{{ route('agenda', ['date' => $date->copy()->addDay()->toDateString()]) }}"
           class="rounded-xl p-2 hover:bg-gray-100 transition-colors text-gray-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
        </a>

        {{-- Datepicker --}}
        <input type="date" value="{{ $date->toDateString() }}"
               onchange="window.location='{{ route('agenda') }}?date='+this.value"
               class="rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500 w-36">

        {{-- Botão hoje --}}
        <a href="{{ route('agenda') }}"
           class="hidden sm:block rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
            Hoje
        </a>
    </div>

    {{-- ===== GRADE ===== --}}

    {{-- Cabeçalho dos profissionais: sticky vertical, overflow oculto (sincronizado via JS) --}}
    <div class="sticky top-0 z-20 bg-white border-b border-gray-100 overflow-hidden" id="agenda-header">
        <div class="flex min-w-max" id="agenda-header-inner">
            <div class="w-16 shrink-0 border-r border-gray-100 h-12"></div>
            @foreach ($professionals as $professional)
                <div class="w-64 sm:w-80 shrink-0 h-12 border-r border-gray-100 last:border-r-0
                            flex items-center justify-center gap-2 px-2">
                    @if ($professional->photo)
                        <img src="{{ Storage::url($professional->photo) }}"
                             class="h-6 w-6 rounded-full object-cover shrink-0" alt="">
                    @else
                        <div class="h-6 w-6 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 text-xs font-bold shrink-0">
                            {{ strtoupper(substr($professional->name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="text-xs font-semibold text-gray-700 truncate">{{ $professional->name }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Corpo com scroll horizontal --}}
    <div class="overflow-x-auto" id="agenda-body">
        <div class="min-w-max relative">

            {{-- Corpo da grade --}}
            <div class="flex">

            {{-- Coluna de horas --}}
            <div class="sticky left-0 z-20 bg-white border-r border-gray-300 w-16 shrink-0">
                @foreach ($slots as $slot)
                    @php
                        [$slotH, $slotM] = explode(':', $slot);
                        $slotIsUnavailable = (int)$slotH * 60 + (int)$slotM >= $endHour * 60;
                    @endphp
                    <div class="h-12 flex items-center justify-end pr-2">
                        <span class="text-xs font-medium border rounded px-1 py-0.5 leading-none
                            {{ $slotIsUnavailable
                                ? 'text-gray-300 border-gray-200'
                                : 'text-gray-400 border-gray-300' }}">
                            {{ $slot }}
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Colunas por profissional --}}
            @foreach ($professionals as $professional)
                @php
                    $profAppointments = $appointments[$professional->id] ?? collect();
                    $unavailableTopPx    = ($endHour - $startHour) * 2 * 48;
                    $unavailableHeightPx = (24 - $endHour) * 2 * 48;
                @endphp

                <div class="w-64 sm:w-80 shrink-0 border-r border-gray-300 last:border-r-0">

                    {{-- Slots + Cards --}}
                    <div class="relative">

                        {{-- Linhas de slot (fundo clicável / indisponível) --}}
                        @foreach ($slots as $slot)
                            @php
                                [$slotH2, $slotM2] = explode(':', $slot);
                                $isUnavailable = (int)$slotH2 * 60 + (int)$slotM2 >= $endHour * 60;
                                $isHour = str_ends_with($slot, ':00');
                            @endphp
                            <div
                                class="h-12 border-b transition-colors
                                    {{ $isUnavailable
                                        ? 'bg-gray-50 border-gray-100 cursor-default'
                                        : ($isHour
                                            ? 'border-gray-300 cursor-pointer hover:bg-primary-50/40'
                                            : 'border-dashed border-gray-200 cursor-pointer hover:bg-primary-50/40') }}"
                                @if (!$isUnavailable)
                                    @click="openNewAppointment('{{ $professional->id }}', '{{ $date->toDateString() }}', '{{ $slot }}')"
                                @endif
                            ></div>
                        @endforeach

                        {{-- Bloco Indisponível --}}
                        @if ($endHour < 24)
                            <div class="absolute left-1 right-1 rounded-xl bg-gray-100 border border-gray-300 flex flex-col items-center justify-start pt-3 z-[1] pointer-events-none"
                                 style="top: {{ $unavailableTopPx }}px; height: {{ $unavailableHeightPx - 4 }}px;">
                                <p class="text-xs font-bold text-gray-400 tabular-nums">
                                    {{ sprintf('%02d:00', $endHour) }} – 00:00
                                </p>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">Indisponível</p>
                            </div>
                        @endif

                        {{-- Cards de agendamento (com algoritmo de sobreposição em cascata por cluster) --}}
                        @php
                            $sorted    = $profAppointments->sortBy('start_time')->values();
                            $colEnds   = [];  // colIdx => end_time
                            $aptColIdx = [];  // apt->id => colIdx

                            // 1. Atribuir coluna a cada card
                            foreach ($sorted as $a) {
                                $s      = substr($a->start_time, 0, 5);
                                $e      = substr($a->end_time,   0, 5);
                                $placed = false;
                                foreach ($colEnds as $ci => $ce) {
                                    if ($s >= $ce) {
                                        $colEnds[$ci]      = $e;
                                        $aptColIdx[$a->id] = $ci;
                                        $placed            = true;
                                        break;
                                    }
                                }
                                if (!$placed) {
                                    $ci                = count($colEnds);
                                    $colEnds[$ci]      = $e;
                                    $aptColIdx[$a->id] = $ci;
                                }
                            }

                            // 2. Para cada card, calcular quantas colunas usa o seu cluster de sobreposição
                            $aptTotalCols = [];
                            foreach ($sorted as $a) {
                                $aS     = substr($a->start_time, 0, 5);
                                $aE     = substr($a->end_time,   0, 5);
                                $maxCol = 0;
                                foreach ($sorted as $b) {
                                    $bS = substr($b->start_time, 0, 5);
                                    $bE = substr($b->end_time,   0, 5);
                                    if ($aS < $bE && $aE > $bS) {
                                        $maxCol = max($maxCol, $aptColIdx[$b->id]);
                                    }
                                }
                                $aptTotalCols[$a->id] = $maxCol + 1;
                            }
                        @endphp

                        @foreach ($profAppointments as $apt)
                            @php
                                $cfg   = $apt->statusConfig();
                                $ci    = $aptColIdx[$apt->id] ?? 0;
                                $total = $aptTotalCols[$apt->id] ?? 1;
                                $wPct  = 100 / $total;
                                $lPct  = $ci * $wPct;
                                $zIdx  = 10 + $ci;
                            @endphp
                            <div
                                data-apt-id="{{ $apt->id }}"
                                class="absolute rounded-xl border px-2 py-1 cursor-pointer overflow-hidden
                                       {{ $cfg['bg'] }} {{ $cfg['border'] }} hover:brightness-95 transition-all"
                                style="top:{{ $apt->gridTop($startHour) }}px; height:{{ max($apt->gridHeight() - 4, 24) }}px; left:calc({{ $lPct }}% + 2px); width:calc({{ $wPct }}% - 4px); z-index:{{ $zIdx }};"
                                @click.stop="openDetail({{ $apt->load(['client', 'service', 'order.items'])->toJson() }})"
                            >
                                <div class="flex items-start justify-between gap-1">
                                    <p class="text-xs font-bold {{ $cfg['text'] }} leading-tight">
                                        {{ substr($apt->start_time, 0, 5) }} às {{ substr($apt->end_time, 0, 5) }}
                                    </p>
                                    <span data-status-label
                                          class="text-[10px] font-semibold {{ $cfg['text'] }} opacity-80 leading-tight shrink-0">
                                        {{ $cfg['label'] }}
                                    </span>
                                </div>
                                <p class="text-xs font-semibold {{ $cfg['text'] }} truncate leading-tight">
                                    {{ $apt->client->name }}
                                </p>
                                @if ($apt->gridHeight() > 48)
                                    @php
                                        $svcNames = $apt->order?->items
                                            ->where('type', 'service')
                                            ->pluck('description')
                                            ->implode(', ');
                                    @endphp
                                    <p class="text-xs {{ $cfg['text'] }} opacity-75 truncate leading-tight">
                                        {{ $svcNames ?: ($apt->service?->name ?? '') }}
                                    </p>
                                @endif
                            </div>
                        @endforeach

                    </div>
                </div>
            @endforeach

            </div>{{-- fim corpo --}}

            {{-- Estado vazio --}}
            @if ($professionals->isEmpty())
                <div class="flex-1 flex flex-col items-center justify-center py-24 text-gray-400">
                    <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 11-5.196 3 3 3 0 015.196-3zm1.536-.887a2.165 2.165 0 001.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863l2.077-1.199m0-3.328a4.323 4.323 0 012.068-1.379l5.325-1.628a4.5 4.5 0 012.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0010.607 12m3.736 0l7.794 4.5-.802.215a4.5 4.5 0 01-2.48-.043l-5.326-1.629a4.324 4.324 0 01-2.068-1.379M14.343 12l-2.882 1.664"/></svg>
                    <p class="font-medium">Nenhum profissional na agenda</p>
                    <a href="{{ route('professionals') }}" class="mt-2 text-sm text-primary-600 hover:underline">
                        Cadastrar profissional
                    </a>
                </div>
            @endif

        </div>
    </div>{{-- fim agenda-body --}}

    {{-- ===== MODAL: SELECIONAR CLIENTE ===== --}}
    <div x-show="showPickClient" x-cloak
         class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
         style="z-index:100; display:none">
        <div class="absolute inset-0 bg-black/40" @click="closePickClient()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Selecionar Cliente</h2>
                <button @click="closePickClient()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-4 py-3 border-b border-gray-100 flex gap-2">
                <input type="text" x-model="clientSearch" placeholder="Buscar por nome ou telefone…"
                       class="flex-1 rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                <button type="button" @click="openClientFormOverlay()"
                    class="shrink-0 rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    + Novo
                </button>
            </div>
            <div class="px-3 py-2" style="height: 220px; overflow-y: auto; flex-shrink: 0;">
                <template x-for="client in filteredClients()" :key="client.id">
                    <button type="button" @click="selectClient(client)"
                        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-primary-50 transition-colors text-left">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 text-sm" x-text="client.name"></p>
                            <p class="text-xs text-gray-400" x-text="client.phone || 'Sem telefone'"></p>
                        </div>
                    </button>
                </template>
                <p x-show="filteredClients().length === 0" class="text-center text-sm text-gray-400 py-8">Nenhum cliente encontrado.</p>
            </div>
        </div>
    </div>

    {{-- ===== MODAL: SELECIONAR SERVIÇO (múltiplos) ===== --}}
    <div x-show="showPickService" x-cloak
         class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
         style="z-index:100; display:none">
        <div class="absolute inset-0 bg-black/40" @click="closePickService()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Selecionar Serviços</h2>
                <button @click="closePickService()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-4 py-3 border-b border-gray-100 flex gap-2">
                <input type="text" x-model="serviceSearch" placeholder="Buscar serviço…"
                       class="flex-1 rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                <button type="button" @click="openServiceFormOverlay()"
                    class="shrink-0 rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    + Novo
                </button>
            </div>
            <div class="px-3 py-2" style="height: 220px; overflow-y: auto; flex-shrink: 0;">
                <template x-for="service in filteredServices()" :key="service.id">
                    <button type="button" @click="toggleService(service)"
                        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors text-left"
                        :class="isServiceSelected(service.id) ? 'bg-primary-50' : 'hover:bg-gray-50'">
                        <div class="shrink-0 h-5 w-5 rounded-full border-2 flex items-center justify-center transition-colors"
                             :class="isServiceSelected(service.id) ? 'border-primary-500 bg-primary-500' : 'border-gray-300'">
                            <svg x-show="isServiceSelected(service.id)" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 text-sm" x-text="service.name"
                               :class="isServiceSelected(service.id) ? 'text-primary-800' : ''"></p>
                            <p class="text-xs text-gray-400"
                               x-text="service.duration_min + 'min · R$ ' + Number(service.price).toFixed(2).replace('.', ',')"></p>
                        </div>
                    </button>
                </template>
                <p x-show="filteredServices().length === 0" class="text-center text-sm text-gray-400 py-8">Nenhum serviço encontrado.</p>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                <button type="button" @click="confirmServices()"
                    :disabled="selectedServices.length === 0"
                    class="w-full rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50 transition-opacity">
                    <span x-text="selectedServices.length > 0 ? 'Confirmar (' + selectedServices.length + ')' : 'Selecione ao menos um serviço'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== MODAL: NOVO/EDITAR AGENDAMENTO ===== --}}
    <div x-show="showNew" x-cloak
         class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
         style="z-index:100; display:none">
        <div class="absolute inset-0 bg-black/40" @click="showNew = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>

            <div class="flex items-center justify-between px-6" style="padding-top: 1.75rem; padding-bottom: 1rem;">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Editar Agendamento' : 'Novo Agendamento'"></h2>
                <button @click="showNew = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto px-6 pb-6 space-y-4">

            {{-- Alerta de conflito --}}
            <div x-show="showConflict" x-cloak class="rounded-xl bg-yellow-50 border border-yellow-200 p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-yellow-100">
                        <svg class="h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">Conflito de horário detectado</p>
                        <template x-for="c in conflictInfo" :key="c.id">
                            <p class="text-xs text-yellow-700 mt-0.5" x-text="'• ' + c.start_time + ' – ' + c.end_time"></p>
                        </template>
                        <p class="text-xs text-yellow-600 mt-1">Deseja criar o agendamento mesmo assim?</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="showConflict = false"
                        class="flex-1 rounded-xl border border-yellow-300 py-2 text-sm font-medium text-yellow-700 hover:bg-yellow-100">Voltar</button>
                    <button type="button" @click="submitAppointment(true)" :disabled="saving"
                        class="flex-1 rounded-xl bg-primary-600 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">+ Agendamento</button>
                </div>
            </div>

            <form x-show="!showConflict" @submit.prevent="submitAppointment()" class="space-y-4">

                {{-- Cliente: pill clicável --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <button type="button" @click="openPickClientFromForm()"
                        class="w-full rounded-xl border-2 px-3 py-2.5 text-left text-sm transition-colors flex items-center justify-between gap-2"
                        :class="selectedClient ? 'border-primary-300 bg-primary-50' : 'border-dashed border-gray-300 hover:border-primary-300'">
                        <span :class="selectedClient ? 'font-semibold text-primary-700' : 'text-gray-400'"
                              x-text="selectedClient ? selectedClient.name : 'Selecionar cliente…'"></span>
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                </div>

                {{-- Serviços: múltipla seleção --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serviços *</label>
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="selectedServices.length > 0">
                        <template x-for="svc in selectedServices" :key="svc.id">
                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 text-primary-700 text-xs font-medium px-2.5 py-1">
                                <span x-text="svc.name"></span>
                                <button type="button" @click="toggleService(svc); applyServiceDuration()"
                                    class="text-primary-400 hover:text-primary-700 leading-none">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        </template>
                    </div>
                    <button type="button" @click="openPickServiceFromForm()"
                        class="w-full rounded-xl border-2 px-3 py-2.5 text-left text-sm transition-colors flex items-center justify-between gap-2"
                        :class="selectedServices.length ? 'border-primary-300 bg-primary-50' : 'border-dashed border-gray-300 hover:border-primary-300'">
                        <span :class="selectedServices.length ? 'font-semibold text-primary-700' : 'text-gray-400'"
                              x-text="selectedServices.length ? '+ Adicionar / alterar serviços' : 'Selecionar serviço…'"></span>
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                </div>

                {{-- Profissional --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Profissional *</label>
                    <select x-model="form.professional_id" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        @foreach ($professionals as $prof)
                            <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Data e horários --}}
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                        <input type="date" x-model="form.date" required
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Início</label>
                        <input type="time" x-model="form.start_time" step="900" @change="recalcEndTime()"
                               class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fim</label>
                        <input type="time" x-model="form.end_time" step="900"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                {{-- Recorrência --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recorrência</label>
                    <select x-model="form.recurrence"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="none">Não repetir</option>
                        <option value="weekly">Semanal (12x)</option>
                        <option value="biweekly">Quinzenal (12x)</option>
                        <option value="monthly">Mensal (12x)</option>
                    </select>
                </div>

                {{-- Toggle comanda --}}
                <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Criar comanda</p>
                        <p class="text-xs text-gray-400">Abre automaticamente ao confirmar</p>
                    </div>
                    <input type="checkbox" x-model="form.create_order"
                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                </label>

                {{-- Observações --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea x-model="form.notes" rows="2"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>

                <p x-show="formError" x-text="formError" class="text-sm text-red-500 text-center"></p>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showNew = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="saving || !selectedClient || !selectedServices.length"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
                        <span x-show="!saving">Agendar</span>
                        <span x-show="saving">Salvando…</span>
                    </button>
                </div>
            </form>
            </div>{{-- fim scroll --}}
        </div>
    </div>

    {{-- ===== MODAL: FORMULÁRIO CLIENTE ===== --}}
    <div x-show="showClientForm" x-cloak
         class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
         style="z-index:110; display:none">
        <div class="absolute inset-0 bg-black/50" @click="showClientForm = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>
            <div class="flex items-center justify-between px-6" style="padding-top: 1.75rem; padding-bottom: 1rem;">
                <h2 class="text-lg font-semibold text-gray-900">Novo Cliente</h2>
                <button @click="showClientForm = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto px-6 pb-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" x-model="clientFormData.name"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="tel" x-model="clientFormData.phone"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Aniversário</label>
                        <input type="date" x-model="clientFormData.birthday"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" x-model="clientFormData.email"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <p x-show="clientFormError" x-text="clientFormError" class="text-sm text-red-500"></p>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="showClientForm = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="button" @click="saveClientForm()" :disabled="clientFormSaving"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
                        <span x-show="!clientFormSaving">Salvar</span>
                        <span x-show="clientFormSaving">Salvando…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MODAL: FORMULÁRIO SERVIÇO ===== --}}
    <div x-show="showServiceForm" x-cloak
         class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
         style="z-index:110; display:none">
        <div class="absolute inset-0 bg-black/50" @click="showServiceForm = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>
            <div class="flex items-center justify-between px-6" style="padding-top: 1.75rem; padding-bottom: 1rem;">
                <h2 class="text-lg font-semibold text-gray-900">Novo Serviço</h2>
                <button @click="showServiceForm = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto px-6 pb-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" x-model="serviceFormData.name"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select x-model="serviceFormData.category_id"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Sem categoria</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço (R$) *</label>
                        <input type="number" x-model.number="serviceFormData.price" step="0.01" min="0"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duração (min) *</label>
                        <input type="number" x-model.number="serviceFormData.duration_min" min="5" step="5"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comissão (%)</label>
                    <input type="number" x-model.number="serviceFormData.commission_pct" min="0" max="100" step="0.5"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <p x-show="serviceFormError" x-text="serviceFormError" class="text-sm text-red-500"></p>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="showServiceForm = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="button" @click="saveServiceForm()" :disabled="serviceFormSaving"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
                        <span x-show="!serviceFormSaving">Salvar</span>
                        <span x-show="serviceFormSaving">Salvando…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MODAL: DETALHES DO AGENDAMENTO ===== --}}
    <div x-show="showDetail" x-cloak
         class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
         style="z-index:100; display:none">
        <div class="absolute inset-0 bg-black/40" @click="showDetail = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>

            {{-- Barra colorida no topo conforme status --}}
            <div class="h-1.5 w-full" :class="statusBg(detail?.status)"></div>

            <div class="px-5 pt-6 pb-5 space-y-4">

                {{-- Header: foto + nome + saldo + fechar --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Avatar --}}
                        <div class="shrink-0">
                            <img x-show="detail?.client?.photo"
                                 :src="'/storage/' + detail?.client?.photo"
                                 class="h-11 w-11 rounded-full object-cover">
                            <div x-show="!detail?.client?.photo"
                                 class="h-11 w-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold text-base"
                                 x-text="detail?.client?.name?.charAt(0)?.toUpperCase()">
                            </div>
                        </div>
                        {{-- Nome + saldo --}}
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 text-base truncate" x-text="detail?.client?.name"></p>
                            <p x-show="detail?.client?.balance > 0"
                               class="text-xs font-medium text-green-600"
                               x-text="'Crédito: R$ ' + Number(detail?.client?.balance).toFixed(2).replace('.', ',')"></p>
                            <p x-show="detail?.client?.balance < 0"
                               class="text-xs font-medium text-red-600"
                               x-text="'Débito: R$ ' + Number(Math.abs(detail?.client?.balance)).toFixed(2).replace('.', ',')"></p>
                            <p x-show="!detail?.client?.balance || detail?.client?.balance == 0"
                               class="text-xs text-gray-400">Sem saldo</p>
                        </div>
                    </div>
                    <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600 shrink-0 mt-0.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Data, hora e serviço --}}
                <div class="rounded-xl bg-gray-50 px-3 py-2.5 space-y-1">
                    <p class="text-sm text-gray-700">
                        <span class="font-medium" x-text="detail?.date?.substring(0,10)"></span>
                        <span class="text-gray-400 mx-1">·</span>
                        <span x-text="detail?.start_time?.substring(0,5) + ' – ' + detail?.end_time?.substring(0,5)"></span>
                    </p>
                    <p class="text-sm text-gray-500"
                       x-text="detail?.order?.items?.filter(i => i.type === 'service').map(i => i.description).join(', ') || detail?.service?.name || ''"></p>
                </div>

                {{-- Status badge --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-3 py-1 rounded-full"
                          :class="statusBadge(detail?.status)"
                          x-text="statusLabel(detail?.status)"></span>
                </div>

                {{-- Observações --}}
                <p x-show="detail?.notes" x-text="detail?.notes"
                   class="text-sm text-gray-500 bg-gray-50 rounded-xl px-3 py-2"></p>

                {{-- Alterar status --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Alterar status
                    </label>
                    <div class="flex gap-2">
                        <button @click="changeStatus('scheduled')"
                            :class="detail?.status === 'scheduled'
                                ? 'bg-blue-600 text-white border border-blue-700'
                                : 'bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200'"
                            class="flex-1 text-sm font-medium py-2 rounded-xl border transition-colors whitespace-nowrap">
                            Agendado
                        </button>
                        <button @click="changeStatus('confirmed')"
                            :class="detail?.status === 'confirmed'
                                ? 'bg-green-600 text-white border border-green-700'
                                : 'bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200'"
                            class="flex-1 text-sm font-medium py-2 rounded-xl border transition-colors whitespace-nowrap">
                            Confirmado
                        </button>
                        <button @click="changeStatus('completed')"
                            :class="detail?.status === 'completed'
                                ? 'bg-gray-600 text-white border border-gray-700'
                                : 'bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200'"
                            class="flex-1 text-sm font-medium py-2 rounded-xl border transition-colors whitespace-nowrap">
                            Finalizado
                        </button>
                        <button @click="changeStatus('cancelled')"
                            :class="detail?.status === 'cancelled'
                                ? 'bg-red-600 text-white border border-red-700'
                                : 'bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200'"
                            class="flex-1 text-sm font-medium py-2 rounded-xl border transition-colors whitespace-nowrap">
                            Cancelado
                        </button>
                    </div>
                </div>

                {{-- Ações --}}
                <div class="flex gap-2 pt-1">
                    <button x-show="detail?.order_id"
                            @click="showDetail = false; $dispatch('open-order-modal', { orderId: detail.order_id })"
                            class="flex-1 rounded-xl bg-primary-50 border border-primary-200 py-2.5 text-sm font-semibold
                                   text-primary-700 text-center hover:bg-primary-100">
                        Ver Comanda
                    </button>
                    <button @click="openAtSameSlot()"
                        class="flex-1 rounded-xl bg-primary-50 border border-primary-200 py-2.5 text-sm font-semibold text-primary-600 hover:bg-primary-100">
                        Agendamento
                    </button>
                    <button @click="openEditAppointment()"
                        class="flex-1 rounded-xl bg-primary-50 border border-primary-200 py-2.5 text-sm font-semibold text-primary-600 hover:bg-primary-100">
                        Editar
                    </button>
                    <button @click="confirmDelete = true"
                        class="flex-1 rounded-xl border border-red-200 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                        Excluir
                    </button>
                </div>

                {{-- Recorrência: excluir todas? --}}
                <div x-show="detail?.recurrence !== 'none'" class="pt-0">
                    <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer">
                        <input type="checkbox" x-model="deleteAll"
                               class="rounded border-gray-300 text-primary-500">
                        Excluir todos os agendamentos desta série
                    </label>
                </div>
            </div>

            {{-- Confirmação de exclusão --}}
            <div x-show="confirmDelete" class="absolute inset-0 rounded-2xl bg-white flex flex-col items-center justify-center p-6 space-y-4" style="display:none">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-900">Confirmar exclusão</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Excluir o agendamento de <span class="font-medium" x-text="detail?.client?.name"></span>?
                        <span x-show="deleteAll" class="block mt-1 text-primary-600 font-medium">Todos da série serão removidos.</span>
                    </p>
                </div>
                <div class="flex gap-3 w-full">
                    <button @click="confirmDelete = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button @click="deleteAppointment()"
                        class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function agenda() {
    @php
        $aptsByProfJson = $appointments->map(fn($apts) => $apts->map(fn($a) => [
            'id'         => $a->id,
            'start_time' => substr($a->start_time, 0, 5),
            'end_time'   => substr($a->end_time,   0, 5),
        ])->values());
    @endphp
    const aptsByProfessional = @json($aptsByProfJson);
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    return {
        // ── Estado geral ──────────────────────────────────────────────────
        showNew:         false,
        showDetail:      false,
        showConflict:    false,
        conflictInfo:    [],
        saving:          false,
        formError:       '',
        deleteAll:       false,
        confirmDelete:   false,
        detail:          null,
        editingId:       null,
        statusOverrides: {},

        // ── Picker de cliente/serviço ─────────────────────────────────────
        showPickClient:   false,
        showPickService:  false,
        clientFormContext:  null,   // null | 'fromForm'
        serviceFormContext: null,   // null | 'fromForm'
        clientSearch:     '',
        serviceSearch:    '',
        allClients:        @json($clients),
        allServices:       @json($services),
        categories:        @json($categories),
        selectedClient:    null,
        selectedServices:  [],

        // ── Formulários de criação rápida (z-70) ──────────────────────────
        showClientForm:    false,
        showServiceForm:   false,
        clientFormData:    { name: '', phone: '', email: '', birthday: '', notes: '' },
        serviceFormData:   { name: '', category_id: '', price: '', duration_min: 60, commission_pct: 0 },
        clientFormError:   '',
        serviceFormError:  '',
        clientFormSaving:  false,
        serviceFormSaving: false,

        // ── Formulário de agendamento ─────────────────────────────────────
        form: {
            client_id: '', professional_id: '', service_id: '',
            date: '{{ $date->toDateString() }}',
            start_time: '09:00', end_time: '10:00',
            recurrence: 'none', create_order: true, notes: '',
        },

        // ── Filtros ───────────────────────────────────────────────────────
        filteredClients() {
            const q = this.clientSearch.toLowerCase().trim();
            if (!q) return this.allClients;
            return this.allClients.filter(c =>
                c.name.toLowerCase().includes(q) || (c.phone || '').includes(q)
            );
        },

        filteredServices() {
            const q = this.serviceSearch.toLowerCase().trim();
            if (!q) return this.allServices;
            return this.allServices.filter(s => s.name.toLowerCase().includes(q));
        },

        // ── Fluxo: abrir agendamento (inicia no picker de cliente) ────────
        openNewAppointment(professionalId, date, slot) {
            this.editingId     = null;
            this.showConflict  = false;
            this.conflictInfo  = [];
            this.selectedClient   = null;
            this.selectedServices = [];
            this.clientSearch     = '';
            this.formError        = '';
            this.form = {
                client_id: '', professional_id: professionalId, service_id: '',
                date, start_time: slot, end_time: this.addMinutes(slot, 60),
                recurrence: 'none', create_order: true, notes: '',
            };
            this.clientFormContext  = null;
            this.serviceFormContext = null;
            this.showPickClient = true;
        },

        // ── Fechar pickers sem perder o form de agendamento ───────────────
        closePickClient() {
            this.showPickClient = false;
            if (this.clientFormContext === 'fromForm') {
                this.clientFormContext = null;
                this.showNew = true;
            }
        },

        closePickService() {
            this.showPickService = false;
            if (this.serviceFormContext === 'fromForm') {
                this.serviceFormContext = null;
                this.showNew = true;
            }
        },

        // ── Selecionar cliente no picker ──────────────────────────────────
        selectClient(client) {
            this.selectedClient   = client;
            this.form.client_id   = client.id;
            this.showPickClient   = false;
            if (this.clientFormContext === 'fromForm') {
                this.clientFormContext = null;
                this.showNew = true;
            } else {
                this.clientFormContext = null;
                this.serviceSearch = '';
                this.showPickService = true;
            }
        },

        // ── Selecionar/desselecionar serviço no picker (múltiplos) ───────
        isServiceSelected(id) {
            return this.selectedServices.some(s => s.id === id);
        },

        toggleService(service) {
            const idx = this.selectedServices.findIndex(s => s.id === service.id);
            if (idx >= 0) {
                this.selectedServices.splice(idx, 1);
            } else {
                this.selectedServices.push(service);
            }
        },

        confirmServices() {
            this.applyServiceDuration();
            this.showPickService  = false;
            if (this.serviceFormContext === 'fromForm') {
                this.serviceFormContext = null;
                this.showNew = true;
            } else {
                this.serviceFormContext = null;
                this.showNew = true;
            }
        },

        // ── Abrir picker a partir do form de agendamento ──────────────────
        openPickClientFromForm() {
            this.showNew           = false;
            this.clientSearch      = '';
            this.clientFormContext = 'fromForm';
            this.showPickClient    = true;
        },

        openPickServiceFromForm() {
            this.showNew            = false;
            this.serviceSearch      = '';
            this.serviceFormContext = 'fromForm';
            this.showPickService    = true;
        },

        // ── Abrir formulário de criação por cima do picker ────────────────
        openClientFormOverlay() {
            this.clientFormData  = { name: this.clientSearch, phone: '', email: '', birthday: '', notes: '' };
            this.clientFormError = '';
            this.showClientForm  = true;
        },

        openServiceFormOverlay() {
            this.serviceFormData  = { name: this.serviceSearch, category_id: '', price: '', duration_min: 60, commission_pct: 0 };
            this.serviceFormError = '';
            this.showServiceForm  = true;
        },

        // ── Salvar cliente via AJAX ───────────────────────────────────────
        async saveClientForm() {
            if (!this.clientFormData.name.trim()) { this.clientFormError = 'Informe o nome.'; return; }
            this.clientFormSaving = true; this.clientFormError = '';
            try {
                const res = await fetch('/clients', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify(this.clientFormData),
                });
                if (res.ok) {
                    const client = await res.json();
                    this.allClients.push({ id: client.id, name: client.name, phone: client.phone });
                    this.allClients.sort((a, b) => a.name.localeCompare(b.name, 'pt-BR'));
                    this.showClientForm = false;
                    this.selectClient({ id: client.id, name: client.name, phone: client.phone });
                } else {
                    const err = await res.json().catch(() => ({}));
                    this.clientFormError = err.message || 'Erro ao salvar.';
                }
            } catch { this.clientFormError = 'Erro de conexão.'; }
            finally  { this.clientFormSaving = false; }
        },

        // ── Salvar serviço via AJAX ───────────────────────────────────────
        async saveServiceForm() {
            if (!this.serviceFormData.name.trim() || !this.serviceFormData.price) {
                this.serviceFormError = 'Informe nome e preço.'; return;
            }
            this.serviceFormSaving = true; this.serviceFormError = '';
            try {
                const res = await fetch('/services', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify(this.serviceFormData),
                });
                if (res.ok) {
                    const service = await res.json();
                    const svc = { id: service.id, name: service.name, price: service.price, duration_min: service.duration_min };
                    this.allServices.push(svc);
                    this.allServices.sort((a, b) => a.name.localeCompare(b.name, 'pt-BR'));
                    this.showServiceForm = false;
                    if (!this.isServiceSelected(svc.id)) this.selectedServices.push(svc);
                    this.confirmServices();
                } else {
                    const err = await res.json().catch(() => ({}));
                    this.serviceFormError = err.message || 'Erro ao salvar.';
                }
            } catch { this.serviceFormError = 'Erro de conexão.'; }
            finally  { this.serviceFormSaving = false; }
        },

        // ── Abrir na mesma slot (do detail) ──────────────────────────────
        openAtSameSlot() {
            const profId = this.detail.professional_id;
            const date   = this.detail.date?.substring(0, 10);
            const slot   = this.detail.start_time?.substring(0, 5);
            this.showDetail = false;
            this.openNewAppointment(profId, date, slot);
        },

        // ── Editar agendamento (vai direto ao form) ───────────────────────
        openEditAppointment() {
            this.editingId = this.detail.id;
            this.form = {
                client_id:       this.detail.client_id,
                professional_id: this.detail.professional_id,
                service_id:      this.detail.service_id ?? '',
                date:            this.detail.date?.substring(0, 10),
                start_time:      this.detail.start_time,
                end_time:        this.detail.end_time,
                recurrence:      this.detail.recurrence ?? 'none',
                create_order:    this.detail.create_order ?? false,
                notes:           this.detail.notes ?? '',
            };
            this.selectedClient   = this.allClients.find(c => c.id === this.detail.client_id)
                                    || this.detail.client
                                    || null;
            const svc = this.allServices.find(s => s.id === this.detail.service_id) || this.detail.service || null;
            this.selectedServices = svc ? [svc] : [];
            this.formError  = '';
            this.showDetail = false;
            this.showNew    = true;
        },

        // ── Helpers de horário ────────────────────────────────────────────
        applyServiceDuration() {
            const total = this.selectedServices.reduce((sum, s) => sum + (s.duration_min || 0), 0);
            if (total > 0) {
                this.form.end_time = this.addMinutes(this.form.start_time, total);
            }
        },

        recalcEndTime() {
            const mins = this.selectedServices.reduce((sum, s) => sum + (s.duration_min || 0), 0) || 60;
            this.form.end_time = this.addMinutes(this.form.start_time, mins);
        },

        addMinutes(time, mins) {
            const [h, m] = time.split(':').map(Number);
            const total  = h * 60 + m + mins;
            return String(Math.floor(total / 60) % 24).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
        },

        // ── Conflitos ─────────────────────────────────────────────────────
        getConflicts() {
            const apts     = aptsByProfessional[this.form.professional_id] ?? [];
            const newStart = this.form.start_time.substring(0, 5);
            const newEnd   = this.form.end_time.substring(0, 5);
            return apts.filter(a => {
                if (this.editingId && a.id === this.editingId) return false;
                return newStart < a.end_time && newEnd > a.start_time;
            });
        },

        // ── Submeter agendamento ──────────────────────────────────────────
        async submitAppointment(force = false) {
            if (!this.form.client_id)        { this.formError = 'Selecione um cliente.';  return; }
            if (!this.selectedServices.length) { this.formError = 'Selecione ao menos um serviço.'; return; }
            if (!force) {
                const conflicts = this.getConflicts();
                if (conflicts.length > 0) { this.conflictInfo = conflicts; this.showConflict = true; return; }
            }
            this.showConflict = false;
            this.saving       = true;
            this.formError    = '';
            try {
                const url    = this.editingId ? `/appointments/${this.editingId}` : '{{ route('appointments.store') }}';
                const method = this.editingId ? 'PATCH' : 'POST';
                const payload = {
                    ...this.form,
                    service_id:  this.selectedServices[0]?.id ?? '',
                    service_ids: this.selectedServices.map(s => s.id),
                };
                const res    = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify(payload),
                });
                if (res.ok) {
                    this.showNew   = false;
                    this.editingId = null;
                    window.location.reload();
                } else {
                    const err = await res.json().catch(() => ({}));
                    this.formError = err.message || 'Erro ao salvar. Verifique os campos.';
                }
            } catch { this.formError = 'Erro de conexão.'; }
            finally  { this.saving = false; }
        },

        // ── Detail ────────────────────────────────────────────────────────
        openDetail(appointment) {
            this.detail = { ...appointment };
            if (this.statusOverrides[appointment.id] !== undefined) {
                this.detail.status = this.statusOverrides[appointment.id];
            }
            this.deleteAll     = false;
            this.confirmDelete = false;
            this.showDetail    = true;
        },

        async changeStatus(status) {
            this.statusOverrides[this.detail.id] = status;
            this.detail.status = status;
            this.showDetail    = false;

            if (status === 'completed' && this.detail.order_id) {
                window.dispatchEvent(new CustomEvent('open-order-modal', { detail: { orderId: this.detail.order_id } }));
            }

            await fetch(`/appointments/${this.detail.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ status }),
            });

            const card = document.querySelector(`[data-apt-id="${this.detail.id}"]`);
            if (card) {
                const bgMap     = { scheduled:'bg-blue-100',    confirmed:'bg-green-100', completed:'bg-gray-100', cancelled:'bg-red-100'   };
                const borderMap = { scheduled:'border-blue-300', confirmed:'border-green-300', completed:'border-gray-300', cancelled:'border-red-300' };
                const textMap   = { scheduled:'text-blue-700',  confirmed:'text-green-700', completed:'text-gray-600', cancelled:'text-red-600' };
                const labelMap  = { scheduled:'Agendado', confirmed:'Confirmado', completed:'Finalizado', cancelled:'Cancelado' };

                Object.values(bgMap).forEach(c => card.classList.remove(c));
                Object.values(borderMap).forEach(c => card.classList.remove(c));
                card.classList.add(bgMap[status] ?? 'bg-gray-100', borderMap[status] ?? 'border-gray-300');

                card.querySelectorAll('p, span[data-status-label]').forEach(el => {
                    Object.values(textMap).forEach(c => el.classList.remove(c));
                    if (textMap[status]) el.classList.add(textMap[status]);
                });
                const labelEl = card.querySelector('[data-status-label]');
                if (labelEl) labelEl.textContent = labelMap[status] ?? status;
            }
        },

        async deleteAppointment() {
            this.confirmDelete = false;
            await fetch(`/appointments/${this.detail.id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ delete_recurrence: this.deleteAll }),
            });
            this.showDetail = false;
            window.location.reload();
        },

        // ── Helpers de status ─────────────────────────────────────────────
        statusLabel(status) {
            return { scheduled:'Agendado', confirmed:'Confirmado', completed:'Finalizado', cancelled:'Cancelado' }[status] ?? status;
        },
        statusBg(status) {
            return { scheduled:'bg-blue-400', confirmed:'bg-green-400', completed:'bg-gray-400', cancelled:'bg-red-400' }[status] ?? 'bg-gray-300';
        },
        statusBadge(status) {
            return { scheduled:'bg-blue-100 text-blue-700', confirmed:'bg-green-100 text-green-700', completed:'bg-gray-100 text-gray-600', cancelled:'bg-red-100 text-red-600' }[status] ?? 'bg-gray-100 text-gray-500';
        },
    };
}
</script>

<script>
// Sincroniza scroll horizontal do cabeçalho com o corpo da grade
document.addEventListener('DOMContentLoaded', () => {
    const body   = document.getElementById('agenda-body');
    const header = document.getElementById('agenda-header-inner');
    if (body && header) {
        body.addEventListener('scroll', () => {
            header.style.transform = `translateX(-${body.scrollLeft}px)`;
        });
    }
});
</script>

<x-order-modal />

@endsection