@extends('layouts.app')
@section('title', 'Agenda — Gestão Beauty')

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
                <div class="w-56 sm:w-72 shrink-0 h-12 border-r border-gray-100 last:border-r-0
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

                <div class="w-56 sm:w-72 shrink-0 border-r border-gray-300 last:border-r-0">

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

                        {{-- Cards de agendamento --}}
                        @foreach ($profAppointments as $apt)
                            @php $cfg = $apt->statusConfig(); @endphp
                            <div
                                data-apt-id="{{ $apt->id }}"
                                class="absolute left-1 right-1 rounded-xl border px-2 py-1 cursor-pointer overflow-hidden z-10
                                       {{ $cfg['bg'] }} {{ $cfg['border'] }} hover:brightness-95 transition-all"
                                style="top: {{ $apt->gridTop($startHour) }}px; height: {{ max($apt->gridHeight() - 4, 24) }}px;"
                                @click.stop="openDetail({{ $apt->load('client')->toJson() }})"
                            >
                                <p class="text-xs font-bold {{ $cfg['text'] }} leading-tight">
                                    {{ substr($apt->start_time, 0, 5) }} às {{ substr($apt->end_time, 0, 5) }}
                                </p>
                                <p class="text-xs font-semibold {{ $cfg['text'] }} truncate leading-tight">
                                    {{ $apt->client->name }}
                                </p>
                                @if ($apt->gridHeight() > 48)
                                    <p class="text-xs {{ $cfg['text'] }} opacity-75 truncate leading-tight">
                                        – {{ $apt->service?->name ?? '' }}
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

    {{-- ===== MODAL: NOVO AGENDAMENTO ===== --}}
    <div x-show="showNew" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="showNew = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>

            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Editar Agendamento' : 'Novo Agendamento'"></h2>
                <button @click="showNew = false" class="text-gray-400 hover:text-gray-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <form @submit.prevent="submitAppointment()" class="space-y-4">

                {{-- Cliente --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select x-model="form.client_id" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Selecione o cliente…</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} {{ $client->phone ? '· '.$client->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Serviço --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serviço *</label>
                    <select x-model="form.service_id" @change="applyServiceDuration()" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Selecione o serviço…</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}"
                                    data-duration="{{ $service->duration_min }}">
                                {{ $service->name }} — {{ $service->duration_min }}min — R$ {{ number_format($service->price, 2, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
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
                        <input type="time" x-model="form.start_time" step="900"
                               @change="recalcEndTime()"
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

                {{-- Erro --}}
                <p x-show="formError" x-text="formError" class="text-sm text-red-500 text-center"></p>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showNew = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
                        <span x-show="!saving">Agendar</span>
                        <span x-show="saving">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== MODAL: DETALHES DO AGENDAMENTO ===== --}}
    <div x-show="showDetail" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="showDetail = false"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.stop>

            {{-- Barra colorida no topo conforme status --}}
            <div class="h-1.5 w-full" :class="statusBg(detail?.status)"></div>

            <div class="p-5 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-bold text-gray-900 text-base" x-text="detail?.client?.name"></p>
                        <p class="text-sm text-gray-400"
                           x-text="detail?.date?.substring(0,10) + ' · ' + detail?.start_time + ' – ' + detail?.end_time"></p>
                    </div>
                    <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600 mt-0.5"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
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
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (\App\Models\Appointment::$statusConfig as $key => $cfg)
                            <button
                                @click="changeStatus('{{ $key }}')"
                                :class="detail?.status === '{{ $key }}'
                                    ? '{{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $cfg['border'] }} border'
                                    : 'bg-gray-100 text-gray-500 border border-transparent hover:bg-gray-200'"
                                class="text-xs font-medium px-2.5 py-1.5 rounded-lg transition-colors">
                                {{ $cfg['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Ações --}}
                <div class="flex gap-2 pt-1">
                    <a x-show="detail?.order_id"
                       :href="'/orders/' + detail?.order_id"
                       class="flex-1 rounded-xl bg-primary-50 border border-primary-200 py-2.5 text-sm font-semibold
                              text-primary-700 text-center hover:bg-primary-100">
                        Ver Comanda
                    </a>
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
    const services = @json($services->keyBy('id'));

    return {
        showNew: false,
        showDetail: false,
        saving: false,
        formError: '',
        deleteAll: false,
        confirmDelete: false,
        detail: null,
        editingId: null,

        form: {
            client_id: '',
            professional_id: '',
            service_id: '',
            date: '{{ $date->toDateString() }}',
            start_time: '09:00',
            end_time: '10:00',
            recurrence: 'none',
            create_order: true,
            notes: '',
        },

        openNewAppointment(professionalId, date, slot) {
            this.editingId = null;
            this.form = {
                client_id: '',
                professional_id: professionalId,
                service_id: '',
                date: date,
                start_time: slot,
                end_time: this.addMinutes(slot, 60),
                recurrence: 'none',
                create_order: true,
                notes: '',
            };
            this.formError = '';
            this.showNew = true;
        },

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
            this.formError = '';
            this.showDetail = false;
            this.showNew = true;
        },

        applyServiceDuration() {
            const svc = services[this.form.service_id];
            if (svc) {
                this.form.end_time = this.addMinutes(this.form.start_time, svc.duration_min);
            }
        },

        recalcEndTime() {
            const svc = services[this.form.service_id];
            const mins = svc ? svc.duration_min : 60;
            this.form.end_time = this.addMinutes(this.form.start_time, mins);
        },

        addMinutes(time, mins) {
            const [h, m] = time.split(':').map(Number);
            const total = h * 60 + m + mins;
            return String(Math.floor(total / 60) % 24).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
        },

        async submitAppointment() {
            this.saving = true;
            this.formError = '';
            try {
                const url = this.editingId
                    ? `/appointments/${this.editingId}`
                    : '{{ route('appointments.store') }}';
                const method = this.editingId ? 'PATCH' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            || '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(this.form),
                });
                if (res.ok) {
                    this.showNew = false;
                    this.editingId = null;
                    window.location.reload();
                } else {
                    const err = await res.json();
                    this.formError = err.message || 'Erro ao salvar. Verifique os campos.';
                }
            } catch (e) {
                this.formError = 'Erro de conexão.';
            } finally {
                this.saving = false;
            }
        },

        openDetail(appointment) {
            this.detail = appointment;
            this.deleteAll = false;
            this.confirmDelete = false;
            this.showDetail = true;
        },

        async changeStatus(status) {
            this.detail.status = status;

            await fetch(`/appointments/${this.detail.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ status }),
            });

            // Atualiza o card na grade sem reload
            const card = document.querySelector(`[data-apt-id="${this.detail.id}"]`);
            if (card) {
                const bgMap     = { scheduled:'bg-blue-100',   confirmed:'bg-green-100',  in_progress:'bg-yellow-100', completed:'bg-gray-100',  cancelled:'bg-red-100',   no_show:'bg-orange-100'  };
                const borderMap = { scheduled:'border-blue-300', confirmed:'border-green-300', in_progress:'border-yellow-300', completed:'border-gray-300', cancelled:'border-red-300', no_show:'border-orange-300' };
                const textMap   = { scheduled:'text-blue-700', confirmed:'text-green-700', in_progress:'text-yellow-700', completed:'text-gray-600', cancelled:'text-red-600', no_show:'text-orange-700' };

                Object.values(bgMap).forEach(c => card.classList.remove(c));
                Object.values(borderMap).forEach(c => card.classList.remove(c));
                card.classList.add(bgMap[status] ?? 'bg-gray-100', borderMap[status] ?? 'border-gray-300');

                card.querySelectorAll('p').forEach(p => {
                    Object.values(textMap).forEach(c => p.classList.remove(c));
                    if (textMap[status]) p.classList.add(textMap[status]);
                });
            }
        },

        async deleteAppointment() {
            this.confirmDelete = false;
            await fetch(`/appointments/${this.detail.id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ delete_recurrence: this.deleteAll }),
            });
            this.showDetail = false;
            window.location.reload();
        },

        statusLabel(status) {
            const map = {
                scheduled:   'Agendado',
                confirmed:   'Confirmado',
                in_progress: 'Em atendimento',
                completed:   'Finalizado',
                cancelled:   'Cancelado',
                no_show:     'Não compareceu',
            };
            return map[status] ?? status;
        },

        statusBg(status) {
            const map = {
                scheduled:   'bg-blue-400',
                confirmed:   'bg-green-400',
                in_progress: 'bg-yellow-400',
                completed:   'bg-gray-400',
                cancelled:   'bg-red-400',
                no_show:     'bg-orange-400',
            };
            return map[status] ?? 'bg-gray-300';
        },

        statusBadge(status) {
            const map = {
                scheduled:   'bg-blue-100 text-blue-700',
                confirmed:   'bg-green-100 text-green-700',
                in_progress: 'bg-yellow-100 text-yellow-700',
                completed:   'bg-gray-100 text-gray-600',
                cancelled:   'bg-red-100 text-red-600',
                no_show:     'bg-orange-100 text-orange-700',
            };
            return map[status] ?? 'bg-gray-100 text-gray-500';
        },
    }
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
@endsection