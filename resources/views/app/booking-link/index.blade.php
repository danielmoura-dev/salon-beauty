@extends('layouts.app')
@section('title', 'Meu Link — Salon Beauty')

@section('content')
@php
    $publicUrl = route('public.booking.show', $tenant->booking_slug);
@endphp
<div class="max-w-2xl mx-auto space-y-5" x-data="bookingLinkPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meu Link</h1>
            <p class="text-sm text-gray-500">Permita que clientes agendem direto pela web.</p>
        </div>
        <div class="sm:ml-auto flex gap-2">
            <button @click="tab = 'config'"
                :class="tab === 'config' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
                class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                Configuração
            </button>
            <button @click="tab = 'recent'"
                :class="tab === 'recent' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
                class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                Agendamentos recentes
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- ====== ABA CONFIGURAÇÃO ====== --}}
    <div x-show="tab === 'config'" class="space-y-5">

        {{-- Card: Link público --}}
        <div class="rounded-2xl bg-white border border-gray-100 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-gray-700">Seu link de agendamento</p>
                    <p class="text-xs text-gray-400">Compartilhe no Instagram, WhatsApp ou bio.</p>
                </div>
                <form method="POST" action="{{ route('booking-link.update') }}" class="inline">
                    @csrf
                    <input type="hidden" name="booking_interval_min" value="{{ $tenant->booking_interval_min ?? 30 }}">
                    <input type="hidden" name="booking_show_prices"  value="{{ $tenant->booking_show_prices ? 1 : 0 }}">
                    <input type="hidden" name="booking_active"       value="{{ $tenant->booking_active ? 0 : 1 }}">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors
                               {{ $tenant->booking_active ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $tenant->booking_active ? 'bg-white' : 'bg-gray-400' }}"></span>
                        {{ $tenant->booking_active ? 'Ativo' : 'Inativo' }}
                    </button>
                </form>
            </div>
            <div class="flex items-stretch gap-2">
                <input type="text" readonly value="{{ $publicUrl }}" x-ref="linkInput"
                    class="flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-700">
                <button type="button" @click="copyLink()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 text-white px-4 py-2 text-sm font-medium hover:bg-primary-700">
                    <svg x-show="!copied" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m11.25 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                    <svg x-show="copied" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span x-text="copied ? 'Copiado!' : 'Copiar'"></span>
                </button>
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center rounded-xl bg-gray-100 text-gray-600 px-3 py-2 hover:bg-gray-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </a>
            </div>
        </div>

        {{-- Card: Identidade visual + ajustes --}}
        <form method="POST" action="{{ route('booking-link.update') }}" enctype="multipart/form-data"
              id="form-booking-link"
              class="rounded-2xl bg-white border border-gray-100 p-5 shadow-sm space-y-5">
            @csrf

            <p class="text-sm font-semibold text-gray-700">Identidade visual</p>

            {{-- Banner --}}
            <div x-data="bannerCropper('banner-input', 'banner-preview', 'banner-color-input', 'form-booking-link')">
                <label class="block text-xs text-gray-500 mb-2">Banner</label>

                {{-- Preview compacto --}}
                <div id="banner-preview"
                     class="w-full h-20 rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center"
                     style="
                        @if($tenant->banner) background-image: url('{{ Storage::url($tenant->banner) }}'); background-size: cover; background-position: center;
                        @elseif($tenant->booking_banner_color) background-color: {{ $tenant->booking_banner_color }};
                        @endif
                     ">
                    @if (!$tenant->banner && !$tenant->booking_banner_color)
                        <span class="text-xs text-gray-400">Sem banner</span>
                    @endif
                </div>

                <div class="flex items-center gap-2 mt-2">
                    {{-- Botão que abre o modal de edição --}}
                    <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        <span>Editar banner</span>
                        <input id="banner-input" type="file" name="banner" accept="image/*" class="hidden">
                    </label>
                    {{-- Abre diretamente na aba cor --}}
                    <button type="button" @click="bannerTab = 'color'; bannerOpen = true"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                        <span class="inline-block h-3.5 w-3.5 rounded-full border border-gray-300"
                              :style="'background:' + (document.getElementById('banner-color-input')?.value || '{{ $tenant->booking_banner_color ?? '#6366f1' }}')"></span>
                        Escolher cor
                    </button>
                    @if ($tenant->banner || $tenant->booking_banner_color)
                        <button type="button"
                            onclick="document.getElementById('remove-banner-input').value='1'; document.getElementById('banner-preview').style.backgroundImage=''; document.getElementById('banner-preview').style.backgroundColor=''; document.getElementById('banner-preview').innerHTML='<span class=\'text-xs text-gray-400\'>Sem banner</span>';"
                            class="text-xs text-red-500 hover:text-red-700 ml-auto">
                            Remover
                        </button>
                    @endif
                </div>

                <input type="hidden" id="banner-color-input" name="banner_color" value="{{ $tenant->booking_banner_color ?? '' }}">
                <input type="hidden" id="remove-banner-input" name="remove_banner" value="0">

                @include('components.banner-cropper')
            </div>

            {{-- Logo --}}
            <div x-data="imageCropper('logo-input', 'logo-preview', 'form-booking-link')">
                <label class="block text-xs text-gray-500 mb-2">Logo do salão</label>
                <div class="flex items-center gap-3">
                    <div id="logo-preview"
                         class="h-16 w-16 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden cursor-pointer hover:border-primary-400 transition-colors bg-gray-50 shrink-0"
                         @click="document.getElementById('logo-input').click()">
                        @if ($tenant->logo)
                            <img src="{{ Storage::url($tenant->logo) }}" class="h-full w-full object-cover" alt="Logo">
                        @else
                            <svg class="h-7 w-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                        @endif
                    </div>
                    <label class="cursor-pointer text-xs text-primary-600 font-medium hover:underline">
                        {{ $tenant->logo ? 'Trocar logo' : 'Adicionar logo' }}
                        <input id="logo-input" type="file" name="logo" accept="image/*" class="hidden">
                    </label>
                </div>
                @include('components.image-cropper')
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Intervalo entre agendamentos</label>
                    <select name="booking_interval_min"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        @foreach (\App\Http\Controllers\BookingLinkController::ALLOWED_INTERVALS as $min)
                            <option value="{{ $min }}" @selected(($tenant->booking_interval_min ?? 30) == $min)>
                                @if ($min < 60) {{ $min }} minutos
                                @elseif ($min === 60) 1 hora
                                @elseif ($min === 90) 1 hora e 30 min
                                @else {{ intdiv($min,60) }} horas
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer w-full rounded-xl border border-gray-200 px-3 py-2.5">
                        <input type="checkbox" name="booking_show_prices" value="1" @checked($tenant->booking_show_prices)
                            class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-gray-700">Mostrar preços dos serviços</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="booking_active" value="1" @checked($tenant->booking_active)
                        class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm font-medium text-gray-700">Link público ativo</span>
                </label>
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 text-white px-5 py-2 text-sm font-medium hover:bg-primary-700">
                    Salvar
                </button>
            </div>
        </form>

        {{-- Card: Profissionais e serviços --}}
        <div class="rounded-2xl bg-white border border-gray-100 p-5 shadow-sm space-y-3">
            <div>
                <p class="text-sm font-semibold text-gray-700">Profissionais no link</p>
                <p class="text-xs text-gray-400">Ative quem deve aparecer e configure os serviços que cada um realiza.</p>
            </div>

            <div class="space-y-2">
                @forelse ($professionals as $prof)
                    <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 hover:bg-gray-50">
                        @if ($prof->photo)
                            <img src="{{ Storage::url($prof->photo) }}" class="h-10 w-10 rounded-full object-cover shrink-0">
                        @else
                            <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold shrink-0">
                                {{ strtoupper(substr($prof->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 text-sm">{{ $prof->name }}</p>
                            <p class="text-xs text-gray-400">{{ $prof->services->count() }} serviço(s) selecionado(s)</p>
                        </div>
                        <button type="button" @click="openProfessionalModal({{ json_encode([
                            'id' => $prof->id,
                            'name' => $prof->name,
                            'show_on_booking' => (bool) $prof->show_on_booking,
                            'service_ids' => $prof->services->pluck('id')->all(),
                        ]) }})"
                            class="shrink-0 rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                            Configurar
                        </button>
                        <button type="button"
                            @click="toggleProfessional('{{ $prof->id }}', $event.currentTarget)"
                            :data-active="{{ $prof->show_on_booking ? 'true' : 'false' }}"
                            class="shrink-0 relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                   {{ $prof->show_on_booking ? 'bg-primary-600' : 'bg-gray-300' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition
                                   {{ $prof->show_on_booking ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-6">Cadastre profissionais primeiro em <a href="{{ route('professionals') }}" class="text-primary-600 hover:underline">Profissionais</a>.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ====== ABA AGENDAMENTOS RECENTES ====== --}}
    <div x-show="tab === 'recent'" x-cloak class="space-y-2">
        @forelse ($recentAppointments as $apt)
            <div class="rounded-2xl bg-white border border-gray-100 p-4 shadow-sm flex items-center gap-3">
                <div class="h-11 w-11 rounded-full bg-violet-100 flex items-center justify-center text-violet-600 font-bold shrink-0">
                    {{ strtoupper(substr($apt->client?->name ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900 text-sm">{{ $apt->client?->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $apt->service?->name }} · {{ $apt->professional?->name }}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-semibold text-gray-900">{{ $apt->date->format('d/m') }} · {{ substr($apt->start_time, 0, 5) }}</p>
                    @php $cfg = $apt->statusConfig(); @endphp
                    <span class="inline-block mt-1 text-[10px] font-medium px-2 py-0.5 rounded-full {{ $cfg['bg'] }} {{ $cfg['text'] }}">
                        {{ $cfg['label'] }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-gray-100 p-10 text-center">
                <p class="text-sm text-gray-400">Nenhum agendamento recebido pelo link ainda.</p>
            </div>
        @endforelse
    </div>

    {{-- ===== MODAL: Configurar serviços do profissional ===== --}}
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
                <div>
                    <p class="text-xs text-gray-400">Profissional</p>
                    <h2 class="text-lg font-semibold text-gray-900" x-text="editingProf?.name"></h2>
                </div>
                <button @click="profModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="overflow-y-auto px-6 pb-6 space-y-4">
                <p class="text-xs text-gray-500">Marque os serviços que este profissional realiza.</p>

                @php
                    $servicesByCategory = $services->groupBy(fn($s) => $s->category?->name ?? 'Outros');
                @endphp
                @foreach ($servicesByCategory as $catName => $items)
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $catName }}</p>
                        <div class="space-y-1.5">
                            @foreach ($items as $svc)
                                <label class="flex items-center gap-3 rounded-xl border border-gray-100 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" :checked="selectedServiceIds.includes('{{ $svc->id }}')"
                                        @change="toggleServiceSelection('{{ $svc->id }}', $event.target.checked)"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm text-gray-800 flex-1">{{ $svc->name }}</span>
                                    <span class="text-xs text-gray-400">{{ $svc->formattedDuration() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="profModal = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="saveProfessionalServices()" :disabled="savingProf"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 flex items-center justify-center gap-2">
                        <svg x-show="savingProf" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="savingProf ? 'Salvando…' : 'Salvar serviços'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bookingLinkPage() {
    return {
        tab:    'config',
        copied: false,

        profModal:           false,
        editingProf:         null,
        selectedServiceIds:  [],
        savingProf:          false,

        copyLink() {
            const self = this;
            navigator.clipboard.writeText(this.$refs.linkInput.value).then(() => {
                self.copied = true;
                setTimeout(() => { self.copied = false; }, 2000);
            });
        },

        async toggleProfessional(id, btn) {
            const url = '{{ url('/booking-link/professionals') }}/' + id + '/toggle';
            const res = await fetch(url, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });
            if (res.ok) {
                const data = await res.json();
                btn.dataset.active = data.show_on_booking ? 'true' : 'false';
                btn.classList.toggle('bg-primary-600', data.show_on_booking);
                btn.classList.toggle('bg-gray-300',    !data.show_on_booking);
                const knob = btn.querySelector('span');
                knob.classList.toggle('translate-x-6', data.show_on_booking);
                knob.classList.toggle('translate-x-1', !data.show_on_booking);
            }
        },

        openProfessionalModal(prof) {
            this.editingProf        = prof;
            this.selectedServiceIds = [...prof.service_ids];
            this.profModal          = true;
        },

        toggleServiceSelection(id, checked) {
            if (checked) {
                if (!this.selectedServiceIds.includes(id)) this.selectedServiceIds.push(id);
            } else {
                this.selectedServiceIds = this.selectedServiceIds.filter(x => x !== id);
            }
        },

        async saveProfessionalServices() {
            this.savingProf = true;
            const url = '{{ url('/booking-link/professionals') }}/' + this.editingProf.id + '/services';
            const res = await fetch(url, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ service_ids: this.selectedServiceIds }),
            });
            this.savingProf = false;
            if (res.ok) {
                this.profModal = false;
                window.location.reload();
            } else {
                alert('Erro ao salvar.');
            }
        },
    };
}
</script>
@endsection
