@extends('layouts.app')
@section('title', 'Agenda — Gestão Beauty')

@section('content')
<div x-data="agenda()" class="flex flex-col h-full -m-4 sm:-m-6 lg:-m-8">

    {{-- ===== HEADER DA AGENDA ===== --}}
    <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-100 sticky top-0 z-10">

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
                <span class="text-xs text-rose-500 font-medium">Hoje</span>
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
               class="rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500 w-36">

        {{-- Botão hoje --}}
        <a href="{{ route('agenda') }}"
           class="hidden sm:block rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
            Hoje
        </a>
    </div>

    {{-- ===== GRADE ===== --}}
    <div class="flex-1 overflow-auto">
        <div class="flex min-w-max">

            {{-- Coluna de horas --}}
            <div class="sticky left-0 z-10 bg-white border-r border-gray-100 w-16 shrink-0">
                <div class="h-10 border-b border-gray-100"></div> {{-- cabeçalho vazio --}}
                @foreach ($slots as $slot)
                    <div class="h-16 flex items-start justify-end pr-2 pt-1">
                        @if (str_ends_with($slot, ':00'))
                            <span class="text-xs text-gray-400 font-medium">{{ $slot }}</span>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Colunas por profissional --}}
            @foreach ($professionals as $professional)
                @php
                    $profAppointments = $appointments[$professional->id] ?? collect();
                @endphp

                <div class="w-48 sm:w-56 shrink-0 border-r border-gray-100 last:border-r-0">

                    {{-- Cabeçalho do profissional --}}
                    <div class="h-10 border-b border-gray-100 flex items-center justify-center gap-2 px-2 sticky top-[57px] bg-white z-[5]">
                        @if ($professional->photo)
                            <img src="{{ Storage::url($professional->photo) }}"
                                 class="h-6 w-6 rounded-full object-cover shrink-0" alt="">
                        @else
                            <div class="h-6 w-6 rounded-full bg-rose-100 flex items-center justify-center text-rose-500 text-xs font-bold shrink-0">
                                {{ strtoupper(substr($professional->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="text-xs font-semibold text-gray-700 truncate">{{ $professional->name }}</span>
                    </div>

                    {{-- Slots + Cards --}}
                    <div class="relative">

                        {{-- Linhas de slot (fundo clicável) --}}
                        @foreach ($slots as $slot)
                            <div
                                class="h-16 border-b border-gray-50 cursor-pointer hover:bg-rose-50/40 transition-colors
                                       {{ str_ends_with($slot, ':30') ? 'border-dashed' : '' }}"
                                @click="openNewAppointment('{{ $professional->id }}', '{{ $date->toDateString() }}', '{{ $slot }}')"
                            ></div>
                        @endforeach

                        {{-- Cards de agendamento --}}
                        @foreach ($profAppointments as $apt)
                            @php $cfg = $apt->statusConfig(); @endphp
                            <div
                                class="absolute left-1 right-1 rounded-xl border px-2 py-1 cursor-pointer overflow-hidden
                                       {{ $cfg['bg'] }} {{ $cfg['border'] }} hover:brightness-95 transition-all"
                                style="top: {{ $apt->gridTop() }}px; height: {{ max($apt->gridHeight() - 4, 28) }}px;"
                                @click.stop="openDetail({{ $apt->load('client')->toJson() }})"
                            >
                                <p class="text-xs font-semibold {{ $cfg['text'] }} truncate leading-tight">
                                    {{ $apt->start_time }} {{ $apt->client->name }}
                                </p>
                                @if ($apt->gridHeight() > 40)
                                    <p class="text-xs {{ $cfg['text'] }} opacity-70 truncate">
                                        {{ $apt->durationMinutes() }}min
                                    </p>
                                @endif
                            </div>
                        @endforeach

                    </div>
                </div>
            @endforeach

            {{-- Estado vazio --}}
            @if ($professionals->isEmpty())
                <div class="flex-1 flex flex-col items-center justify-center py-24 text-gray-400">
                    <div class="text-4xl mb-3">💇</div>
                    <p class="font-medium">Nenhum profissional na agenda</p>
                    <a href="{{ route('professionals') }}" class="mt-2 text-sm text-rose-600 hover:underline">
                        Cadastrar profissional
                    </a>
                </div>
            @endif

        </div>
    </div>

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
                <button @click="showNew = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <form @submit.prevent="submitAppointment()" class="space-y-4">

                {{-- Cliente --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select x-model="form.client_id" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
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
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
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
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
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
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Início</label>
                        <input type="time" x-model="form.start_time" step="900"
                               @change="recalcEndTime()"
                               class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fim</label>
                        <input type="time" x-model="form.end_time" step="900"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                    </div>
                </div>

                {{-- Recorrência --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recorrência</label>
                    <select x-model="form.recurrence"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
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
                           class="rounded border-gray-300 text-rose-500 focus:ring-rose-500">
                </label>

                {{-- Observações --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea x-model="form.notes" rows="2"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500"></textarea>
                </div>

                {{-- Erro --}}
                <p x-show="formError" x-text="formError" class="text-sm text-red-500 text-center"></p>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showNew = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60">
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
                    <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600 mt-0.5">✕</button>
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
                    <button @click="openEditAppointment()"
                        class="flex-1 rounded-xl bg-rose-50 border border-rose-200 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-100">
                        Editar
                    </button>
                    <button @click="deleteAppointment()"
                        class="flex-1 rounded-xl border border-red-200 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                        Excluir
                    </button>
                </div>

                {{-- Recorrência: excluir todas? --}}
                <div x-show="detail?.recurrence !== 'none'" class="pt-0">
                    <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer">
                        <input type="checkbox" x-model="deleteAll"
                               class="rounded border-gray-300 text-rose-500">
                        Excluir todos os agendamentos desta série
                    </label>
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
        },

        async deleteAppointment() {
            if (!confirm('Confirma exclusão?')) return;
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
@endsection