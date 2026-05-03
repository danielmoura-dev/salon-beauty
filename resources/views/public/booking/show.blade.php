@extends('public.booking.layout')
@section('title', $tenant->name . ' — Agendamento')

@section('content')
<div x-data="bookingPublic({{ json_encode([
        'slug'       => $tenant->booking_slug,
        'showPrices' => (bool) $tenant->booking_show_prices,
        'client'     => $client ? ['id' => $client->id, 'name' => $client->name] : null,
    ]) }})" class="max-w-2xl mx-auto pb-32">

    {{-- Banner --}}
    <div class="relative h-28 sm:h-36 overflow-hidden
        @if(!$tenant->banner && !$tenant->booking_banner_color) bg-gradient-to-br from-primary-500 to-accent-500 @endif"
        @if($tenant->booking_banner_color && !$tenant->banner) style="background-color: {{ $tenant->booking_banner_color }}" @endif>
        @if ($tenant->banner)
            <img src="{{ Storage::url($tenant->banner) }}" alt="" class="absolute inset-0 w-full h-full object-cover">
        @endif
        <div class="absolute inset-0 bg-black/10"></div>
    </div>

    {{-- Cabeçalho --}}
    <div class="px-5 -mt-12 relative">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="h-20 w-20 rounded-2xl overflow-hidden bg-gray-100 flex items-center justify-center shrink-0 border-4 border-white shadow-sm -mt-12">
                    @if ($tenant->logo)
                        <img src="{{ Storage::url($tenant->logo) }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-3xl font-bold text-primary-500">{{ strtoupper(substr($tenant->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold text-gray-900 truncate">{{ $tenant->name }}</h1>
                    <p class="text-sm text-gray-500">Agende seu horário</p>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                @if ($client)
                    <a href="{{ route('public.booking.my-appointments', $tenant->booking_slug) }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-primary-50 text-primary-700 px-4 py-2.5 text-sm font-medium hover:bg-primary-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        Meus agendamentos
                    </a>
                    <form method="POST" action="{{ route('public.booking.auth.logout', $tenant->booking_slug) }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-50">
                            Sair
                        </button>
                    </form>
                @else
                    <button @click="openLoginModal()"
                       class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 text-white px-4 py-2.5 text-sm font-medium hover:bg-primary-700">
                        Entrar pelo WhatsApp
                    </button>
                @endif
            </div>

            @if ($client)
                <p class="mt-3 text-xs text-gray-400 text-center">Olá, <span class="font-semibold text-gray-700">{{ $client->name }}</span></p>
            @endif
        </div>
    </div>

    {{-- Serviços por categoria --}}
    <div class="px-5 mt-6 space-y-5">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-700">Serviços</p>
            <p class="text-xs text-gray-400">Selecione um ou mais</p>
        </div>

        @forelse ($services as $catName => $items)
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 px-1">{{ $catName }}</p>
                <div class="space-y-2">
                    @foreach ($items as $service)
                        <button type="button" @click="toggleService({{ json_encode([
                            'id'           => $service->id,
                            'name'         => $service->name,
                            'duration_min' => (int) $service->duration_min,
                            'price'        => (float) $service->price,
                        ]) }})"
                            :class="isSelected('{{ $service->id }}')
                                ? 'border-primary-400 bg-primary-50 ring-1 ring-primary-300'
                                : 'border-gray-100 bg-white hover:border-primary-200 hover:shadow-sm'"
                            class="w-full text-left flex items-center gap-3 rounded-2xl border p-4 transition">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 truncate">{{ $service->name }}</p>
                                <p class="text-xs text-gray-400">{{ $service->formattedDuration() }}</p>
                            </div>
                            @if ($tenant->booking_show_prices)
                                <span :class="isSelected('{{ $service->id }}') ? 'text-primary-600' : 'text-gray-500'"
                                      class="font-semibold shrink-0 text-sm transition-colors">
                                    R$ {{ number_format($service->price, 2, ',', '.') }}
                                </span>
                            @endif
                            {{-- Circular checkbox --}}
                            <div :class="isSelected('{{ $service->id }}')
                                    ? 'bg-primary-600 border-primary-600'
                                    : 'border-gray-300 bg-white'"
                                 class="h-6 w-6 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors">
                                <svg x-show="isSelected('{{ $service->id }}')" x-cloak
                                     class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-gray-100 p-10 text-center">
                <p class="text-sm text-gray-400">Nenhum serviço disponível no momento.</p>
            </div>
        @endforelse
    </div>

    {{-- ======= BARRA INFERIOR: CTA de agendamento ======= --}}
    <div x-show="selectedServices.length > 0" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-0 left-0 right-0 z-40 px-4 pb-5 pointer-events-none">
        <div class="max-w-2xl mx-auto pointer-events-auto">
            <div class="rounded-2xl bg-white shadow-2xl border border-gray-100 px-4 py-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate" x-text="servicesLabel"></p>
                    <p class="text-xs text-gray-400">
                        <span x-text="totalDurationLabel"></span>
                        <template x-if="showPrices && totalPrice > 0">
                            <span x-text="' · R$ ' + totalPrice.toFixed(2).replace('.', ',')"></span>
                        </template>
                    </p>
                </div>
                <button @click="clearSelection()"
                    class="h-8 w-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-600 shrink-0 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <button @click="proceedToBook()"
                    class="rounded-xl bg-primary-600 text-white px-5 py-2 text-sm font-semibold hover:bg-primary-700 shrink-0 transition-colors">
                    Agendar
                </button>
            </div>
        </div>
    </div>

    {{-- ======= MODAL: LOGIN POR WHATSAPP ======= --}}
    <div x-show="loginModal" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="loginModal = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>
            <div class="flex items-center justify-between px-6" style="padding-top: 1.75rem; padding-bottom: 1rem;">
                <h2 class="text-lg font-semibold text-gray-900">Entrar com WhatsApp</h2>
                <button @click="loginModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="overflow-y-auto px-6 pb-6 space-y-4">
                <div x-show="loginStep === 'phone'" class="space-y-4">
                    <p class="text-sm text-gray-500">Digite seu número de WhatsApp para continuar.</p>
                    <x-form-field label="WhatsApp">
                        <input type="tel" inputmode="tel" :value="phone"
                            @input="phone = formatPhone($event.target.value)"
                            placeholder="(11) 99999-9999"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </x-form-field>
                    <p x-show="loginError" x-text="loginError" class="text-xs text-red-600"></p>
                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="loginModal = false"
                            class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button" @click="checkPhone()" :disabled="loadingAuth"
                            class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 flex items-center justify-center gap-2">
                            <svg x-show="loadingAuth" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span x-text="loadingAuth ? 'Verificando…' : 'Continuar'"></span>
                        </button>
                    </div>
                </div>
                <div x-show="loginStep === 'register'" x-cloak class="space-y-4">
                    <p class="text-sm text-gray-500">Não encontramos seu cadastro. Informe seu nome para criarmos.</p>
                    <x-form-field label="Nome completo">
                        <input type="text" x-model="newName" placeholder="Maria Silva"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </x-form-field>
                    <x-form-field label="WhatsApp">
                        <input type="tel" inputmode="tel" :value="phone"
                            @input="phone = formatPhone($event.target.value)"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </x-form-field>
                    <p x-show="loginError" x-text="loginError" class="text-xs text-red-600"></p>
                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="loginStep = 'phone'"
                            class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Voltar
                        </button>
                        <button type="button" @click="registerClient()" :disabled="loadingAuth"
                            class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 flex items-center justify-center gap-2">
                            <svg x-show="loadingAuth" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span x-text="loadingAuth ? 'Cadastrando…' : 'Criar e continuar'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= MODAL: ESCOLHER PROFISSIONAL ======= --}}
    <div x-show="profModal" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="profModal = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>
            <div class="flex items-center justify-between px-6" style="padding-top: 1.75rem; padding-bottom: 1rem;">
                <div class="min-w-0 flex-1 pr-4">
                    <p class="text-xs text-gray-400">Escolha o profissional</p>
                    <h2 class="text-lg font-semibold text-gray-900 truncate" x-text="servicesLabel"></h2>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="totalDurationLabel"></p>
                </div>
                <button @click="profModal = false" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="overflow-y-auto px-6 pb-6 space-y-2">
                <div x-show="loadingProfs" class="flex items-center justify-center py-8">
                    <svg class="h-5 w-5 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </div>
                <template x-for="prof in professionals" :key="prof.id">
                    <button @click="selectProfessional(prof)"
                        class="w-full flex items-center gap-3 rounded-xl border border-gray-100 p-3 hover:border-primary-300 hover:bg-primary-50 transition text-left">
                        <template x-if="prof.photo">
                            <img :src="prof.photo" class="h-11 w-11 rounded-full object-cover shrink-0">
                        </template>
                        <template x-if="!prof.photo">
                            <div class="h-11 w-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold shrink-0"
                                 x-text="prof.name.substring(0,1).toUpperCase()"></div>
                        </template>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900" x-text="prof.name"></p>
                            <p class="text-xs text-gray-400" x-text="prof.specialty || 'Profissional'"></p>
                        </div>
                        <svg class="h-5 w-5 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </template>
                <p x-show="!loadingProfs && professionals.length === 0" class="text-sm text-gray-400 text-center py-6">
                    Nenhum profissional disponível para os serviços selecionados.
                </p>
            </div>
        </div>
    </div>

    {{-- ======= MODAL: DATA + HORÁRIO ======= --}}
    <div x-show="slotModal" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="slotModal = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>
            <div class="flex items-center justify-between px-6" style="padding-top: 1.75rem; padding-bottom: 1rem;">
                <div class="min-w-0 flex-1 pr-4">
                    <p class="text-xs text-gray-400" x-text="selectedProf?.name"></p>
                    <h2 class="text-lg font-semibold text-gray-900 truncate" x-text="servicesLabel"></h2>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="totalDurationLabel"></p>
                </div>
                <button @click="slotModal = false" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="overflow-y-auto px-6 pb-6 space-y-4">
                <x-form-field label="Data">
                    <input type="date" x-model="selectedDate" :min="todayStr" @change="loadSlots()"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </x-form-field>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-2">Horários disponíveis</p>
                    <div x-show="loadingSlots" class="text-sm text-gray-400 py-3 text-center">Carregando…</div>
                    <div x-show="!loadingSlots && slots.length === 0" class="text-sm text-gray-400 py-3 text-center">Sem horários disponíveis nesta data.</div>
                    <div class="grid grid-cols-3 gap-2" x-show="!loadingSlots && slots.length > 0">
                        <template x-for="t in slots" :key="t">
                            <button @click="selectedSlot = t"
                                :class="selectedSlot === t ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-200 hover:border-primary-300'"
                                class="rounded-xl border px-3 py-2 text-sm font-medium transition" x-text="t"></button>
                        </template>
                    </div>
                </div>
                <p x-show="bookError" x-text="bookError" class="text-xs text-red-600"></p>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="slotModal = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="confirmBooking()" :disabled="!selectedSlot || booking"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 flex items-center justify-center gap-2">
                        <svg x-show="booking" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="booking ? 'Agendando…' : 'Confirmar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= MODAL: SUCESSO ======= --}}
    <div x-show="successModal" x-cloak
         class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="px-6 pt-7 pb-6 text-center">
                <div class="mx-auto h-16 w-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Agendamento confirmado!</h2>
                <p class="text-sm text-gray-500 mt-1" x-text="successMessage"></p>
                <div class="mt-5 flex gap-2">
                    <button @click="resetFlow()"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Novo agendamento
                    </button>
                    <a href="{{ route('public.booking.my-appointments', $tenant->booking_slug) }}"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 flex items-center justify-center">
                        Ver meus
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bookingPublic(cfg) {
    return {
        slug:       cfg.slug,
        showPrices: cfg.showPrices,
        client:     cfg.client,
        todayStr:   new Date().toISOString().slice(0, 10),

        loginModal:  false,
        loginStep:   'phone',
        loadingAuth: false,
        phone:       '',
        newName:     '',
        loginError:  '',

        selectedServices: [],
        profModal:        false,
        loadingProfs:     false,
        professionals:    [],

        selectedProf:  null,
        slotModal:     false,
        selectedDate:  new Date().toISOString().slice(0, 10),
        slots:         [],
        loadingSlots:  false,
        selectedSlot:  null,

        booking:        false,
        bookError:      '',
        successModal:   false,
        successMessage: '',

        pending: null,

        get totalDuration() {
            return this.selectedServices.reduce((sum, s) => sum + s.duration_min, 0);
        },
        get totalPrice() {
            return this.selectedServices.reduce((sum, s) => sum + s.price, 0);
        },
        get totalDurationLabel() {
            const m = this.totalDuration;
            if (m === 0) return '';
            if (m < 60) return m + ' min';
            const h = Math.floor(m / 60);
            const rem = m % 60;
            return rem ? h + 'h ' + rem + 'min' : h + 'h';
        },
        get servicesLabel() {
            const n = this.selectedServices.length;
            if (n === 0) return '';
            if (n === 1) return this.selectedServices[0].name;
            if (n === 2) return this.selectedServices[0].name + ' e ' + this.selectedServices[1].name;
            return this.selectedServices[0].name + ' e mais ' + (n - 1);
        },

        url(path) {
            return '{{ url('agendar') }}/' + this.slug + path;
        },

        formatPhone(value) {
            const d = (value || '').replace(/\D/g, '').slice(0, 11);
            if (d.length <= 2)  return d;
            if (d.length <= 6)  return `(${d.slice(0,2)}) ${d.slice(2)}`;
            if (d.length <= 10) return `(${d.slice(0,2)}) ${d.slice(2,6)}-${d.slice(6)}`;
            return `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
        },

        toggleService(service) {
            const idx = this.selectedServices.findIndex(s => s.id === service.id);
            if (idx >= 0) {
                this.selectedServices.splice(idx, 1);
            } else {
                this.selectedServices.push(service);
            }
        },
        isSelected(id) {
            return this.selectedServices.some(s => s.id === id);
        },
        clearSelection() {
            this.selectedServices = [];
        },

        openLoginModal() {
            this.loginStep  = 'phone';
            this.loginError = '';
            this.loginModal = true;
        },

        async checkPhone() {
            this.loginError = '';
            if (!this.phone.trim()) { this.loginError = 'Informe o WhatsApp.'; return; }
            this.loadingAuth = true;
            const res = await fetch(this.url('/auth/check'), {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body:    JSON.stringify({ phone: this.phone }),
            });
            this.loadingAuth = false;
            if (!res.ok) { this.loginError = 'Erro ao verificar.'; return; }
            const data = await res.json();
            if (data.exists) {
                this.client     = { id: 'pending', name: data.name };
                this.loginModal = false;
                this.afterLogin();
            } else {
                this.loginStep = 'register';
            }
        },

        async registerClient() {
            this.loginError = '';
            if (!this.newName.trim()) { this.loginError = 'Informe seu nome.'; return; }
            this.loadingAuth = true;
            const res = await fetch(this.url('/auth/register'), {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body:    JSON.stringify({ name: this.newName, phone: this.phone }),
            });
            this.loadingAuth = false;
            if (!res.ok) { this.loginError = 'Erro ao cadastrar.'; return; }
            const data = await res.json();
            this.client     = { id: 'pending', name: data.name };
            this.loginModal = false;
            this.afterLogin();
        },

        afterLogin() {
            if (this.pending === 'book') {
                this.openProfessionals();
            }
            this.pending = null;
        },

        proceedToBook() {
            if (this.selectedServices.length === 0) return;
            if (!this.client) {
                this.pending = 'book';
                this.openLoginModal();
                return;
            }
            this.openProfessionals();
        },

        async openProfessionals() {
            this.professionals = [];
            this.loadingProfs  = true;
            this.profModal     = true;
            const params = new URLSearchParams();
            this.selectedServices.forEach(s => params.append('service_ids[]', s.id));
            const res = await fetch(this.url('/professionals?' + params.toString()), {
                headers: { 'Accept': 'application/json' },
            });
            this.loadingProfs = false;
            if (!res.ok) return;
            const data = await res.json();
            this.professionals = data.professionals;
        },

        selectProfessional(prof) {
            this.selectedProf = prof;
            this.profModal    = false;
            this.selectedSlot = null;
            this.bookError    = '';
            this.slotModal    = true;
            this.loadSlots();
        },

        async loadSlots() {
            this.loadingSlots = true;
            this.slots        = [];
            const params = new URLSearchParams({
                professional_id: this.selectedProf.id,
                date:            this.selectedDate,
            });
            this.selectedServices.forEach(s => params.append('service_ids[]', s.id));
            const res = await fetch(this.url('/slots?' + params.toString()), {
                headers: { 'Accept': 'application/json' },
            });
            this.loadingSlots = false;
            if (!res.ok) return;
            const data = await res.json();
            this.slots = data.slots;
        },

        async confirmBooking() {
            this.bookError = '';
            this.booking   = true;
            const res = await fetch(this.url('/book'), {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body:    JSON.stringify({
                    service_ids:     this.selectedServices.map(s => s.id),
                    professional_id: this.selectedProf.id,
                    date:            this.selectedDate,
                    start_time:      this.selectedSlot,
                }),
            });
            this.booking = false;
            const data = await res.json().catch(() => ({}));
            if (!res.ok) { this.bookError = data.error || 'Erro ao agendar.'; return; }
            this.slotModal      = false;
            this.successMessage = `${this.servicesLabel} com ${this.selectedProf.name} em ${this.formatDate(this.selectedDate)} às ${this.selectedSlot}.`;
            this.successModal   = true;
        },

        formatDate(d) {
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        },

        resetFlow() {
            this.successModal     = false;
            this.selectedServices = [];
            this.selectedProf     = null;
            this.selectedSlot     = null;
        },
    };
}
</script>
@endsection
