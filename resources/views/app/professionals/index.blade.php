@extends('layouts.app')
@section('title', 'Profissionais — Salon Beauty')

@section('content')
<div class="space-y-5" x-data="profsPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Profissionais</h1>
            <p class="text-sm text-gray-400">{{ $professionals->count() }} cadastrados</p>
        </div>
        <div class="flex flex-wrap gap-2 sm:ml-auto">
            <a href="{{ route('professionals.commissions') }}"
               class="flex items-center gap-2 rounded-xl border border-green-300 bg-green-50 px-4 py-2.5
                      text-sm font-semibold text-green-700 hover:bg-green-100 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Comissões
            </a>
            <a href="{{ route('professionals.vouchers') }}"
               class="flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5
                      text-sm font-semibold text-amber-700 hover:bg-amber-100 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a3 3 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/></svg>
                Vales
            </a>
            <button @click="openCreate()"
                class="flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5
                       text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                + Novo Profissional
            </button>
        </div>
    </div>

    {{-- Busca --}}
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Buscar por nome…"
               class="flex-1 rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
        <button type="submit"
            class="rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-200">
            Buscar
        </button>
        @if(request('search'))
            <a href="{{ route('professionals') }}"
               class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-500 hover:bg-gray-50">✕</a>
        @endif
    </form>

    {{-- Lista --}}
    <div class="space-y-2">
        @forelse ($professionals as $prof)
            @php
                $serviceCommsMap = $prof->services->mapWithKeys(fn($s) => [
                    $s->id => ['enabled' => true, 'pct' => (string) $s->pivot->commission_pct]
                ]);
            @endphp
            <div class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 px-4 py-3 shadow-sm">

                @if ($prof->photo)
                    <img src="{{ Storage::url($prof->photo) }}" class="h-11 w-11 rounded-full object-cover shrink-0" alt="">
                @else
                    <div class="h-11 w-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold shrink-0">
                        {{ strtoupper(substr($prof->name, 0, 1)) }}
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900 truncate">{{ $prof->name }}</p>
                    <p class="text-sm text-gray-400 truncate">{{ $prof->specialty ?? 'Sem especialidade' }}</p>
                </div>

                <div class="hidden sm:flex gap-1.5 shrink-0">
                    @if ($prof->show_on_agenda)
                        <span class="text-xs bg-blue-50 text-blue-600 rounded-full px-2 py-0.5">Na agenda</span>
                    @endif
                    @if ($prof->receives_commission)
                        <span class="text-xs bg-green-50 text-green-600 rounded-full px-2 py-0.5">Comissão</span>
                    @endif
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button @click="openEdit({{ $prof->toJson() }}, {{ $serviceCommsMap->toJson() }})"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium bg-accent-50 text-accent-600 hover:bg-accent-100 transition-colors">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        Editar
                    </button>
                    <form id="del-prof-{{ $prof->id }}" method="POST" action="{{ route('professionals.destroy', $prof) }}">
                        @csrf @method('DELETE')
                        <button type="button"
                            @click="$dispatch('open-confirm', { formId: 'del-prof-{{ $prof->id }}', message: 'Remover {{ addslashes($prof->name) }}? Esta ação não pode ser desfeita.' })"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            Excluir
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400">
                <p class="font-medium">Nenhum profissional encontrado</p>
            </div>
        @endforelse
    </div>

    {{-- ===== MODAL PROFISSIONAL (multi-etapa) ===== --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl max-h-[92vh] flex flex-col" @click.stop>

            {{-- Cabeçalho do modal --}}
            <div class="flex items-center gap-3 px-6 pt-5 pb-4 border-b border-gray-100 shrink-0">
                <button x-show="step !== 'main'" @click="step = 'main'"
                    class="text-gray-400 hover:text-gray-600 mr-1">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
                <h2 class="text-lg font-semibold text-gray-900 flex-1"
                    x-text="step === 'main' ? (editing ? 'Editar Profissional' : 'Novo Profissional') : (step === 'commissions' ? 'Personalizar Comissões' : 'Horários de Trabalho')">
                </h2>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Formulário principal (sempre presente no DOM) --}}
            <form :action="editing ? `/professionals/${editing.id}` : '{{ route('professionals.store') }}'"
                  method="POST" enctype="multipart/form-data"
                  x-data="{ submitting: false }" @submit="submitting = true"
                  class="flex flex-col flex-1 min-h-0">
                @csrf
                <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

                {{-- Hidden inputs para comissões por serviço --}}
                <template x-for="[sid, d] in Object.entries(serviceCommissions)" :key="sid">
                    <template x-if="d.enabled">
                        <input type="hidden" :name="`custom_commissions[${sid}]`" :value="d.pct">
                    </template>
                </template>

                {{-- Hidden input para horários --}}
                <input type="hidden" name="work_schedule" :value="JSON.stringify(schedule)">

                {{-- Conteúdo scrollável --}}
                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">

                    {{-- ===== ETAPA: PRINCIPAL ===== --}}
                    <div x-show="step === 'main'" class="space-y-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                            <input type="text" name="name" :value="editing?.name" required
                                class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Especialidade</label>
                                <input type="text" name="specialty" :value="editing?.specialty"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Aniversário</label>
                                <input type="date" name="birthday" :value="editing?.birthday?.substring(0, 10)"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                                <span class="text-sm font-medium text-gray-700">Mostrar na agenda</span>
                                <input type="checkbox" name="show_on_agenda" value="1"
                                       :checked="editing ? editing.show_on_agenda : true"
                                       class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                            </label>
                            <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                                <span class="text-sm font-medium text-gray-700">Recebe comissão</span>
                                <input type="checkbox" name="receives_commission" value="1"
                                       :checked="editing ? editing.receives_commission : true"
                                       class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opcional)</label>
                            <input type="file" name="photo" accept="image/*"
                                class="w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0
                                       file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600 file:text-sm
                                       hover:file:bg-primary-100">
                        </div>

                        {{-- Botões de sub-etapas --}}
                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <button type="button" @click="step = 'commissions'"
                                class="flex items-center justify-center gap-2 rounded-xl border-2 border-green-200 bg-green-50
                                       px-4 py-3 text-sm font-semibold text-green-700 hover:bg-green-100 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Comissões
                                <span x-show="Object.keys(serviceCommissions).length > 0"
                                      class="ml-auto bg-green-200 text-green-800 text-xs rounded-full px-1.5 py-0.5"
                                      x-text="Object.keys(serviceCommissions).length + ' custom'"></span>
                            </button>
                            <button type="button" @click="step = 'schedules'"
                                class="flex items-center justify-center gap-2 rounded-xl border-2 border-blue-200 bg-blue-50
                                       px-4 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Horários
                            </button>
                        </div>
                    </div>

                    {{-- ===== ETAPA: COMISSÕES POR SERVIÇO ===== --}}
                    <div x-show="step === 'commissions'" class="space-y-2">
                        <p class="text-xs text-gray-400 pb-1">
                            Marque os serviços com comissão personalizada. Os desmarcados usam a % padrão do serviço.
                        </p>
                        @foreach ($services as $service)
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3"
                                 :class="serviceCommissions['{{ $service->id }}']?.enabled ? 'border-green-200 bg-green-50' : ''">
                                <input type="checkbox"
                                       :checked="serviceCommissions['{{ $service->id }}']?.enabled ?? false"
                                       @change="toggleServiceComm('{{ $service->id }}', $event.target.checked)"
                                       class="rounded border-gray-300 text-green-500 focus:ring-green-400">
                                <span class="flex-1 text-sm font-medium text-gray-800">{{ $service->name }}</span>
                                <span class="text-xs text-gray-400">padrão: {{ $service->commission_pct }}%</span>
                                <div x-show="serviceCommissions['{{ $service->id }}']?.enabled" class="flex items-center gap-1">
                                    <input type="number"
                                           :value="serviceCommissions['{{ $service->id }}']?.pct ?? '{{ $service->commission_pct }}'"
                                           @input="setServicePct('{{ $service->id }}', $event.target.value)"
                                           min="0" max="100" step="0.5"
                                           class="w-20 rounded-lg border-gray-300 text-sm text-right focus:ring-green-400 focus:border-green-400">
                                    <span class="text-sm text-gray-500">%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ===== ETAPA: HORÁRIOS ===== --}}
                    <div x-show="step === 'schedules'" class="space-y-2">
                        <p class="text-xs text-gray-400 pb-1">
                            Configure os horários disponíveis de cada dia. Dias desativados aparecem como folga na agenda.
                        </p>
                        @php
                            $dayLabels = [
                                'monday'    => 'Segunda-feira',
                                'tuesday'   => 'Terça-feira',
                                'wednesday' => 'Quarta-feira',
                                'thursday'  => 'Quinta-feira',
                                'friday'    => 'Sexta-feira',
                                'saturday'  => 'Sábado',
                                'sunday'    => 'Domingo',
                            ];
                        @endphp
                        @foreach ($dayLabels as $dayKey => $dayLabel)
                            <div class="rounded-xl border border-gray-200 px-4 py-3 space-y-3"
                                 :class="schedule['{{ $dayKey }}']?.enabled ? '' : 'bg-gray-50 opacity-60'">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-700">{{ $dayLabel }}</span>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <span class="text-xs text-gray-400"
                                              x-text="schedule['{{ $dayKey }}']?.enabled ? 'Ativo' : 'Folga'"></span>
                                        <input type="checkbox"
                                               :checked="schedule['{{ $dayKey }}']?.enabled ?? true"
                                               @change="schedule['{{ $dayKey }}'].enabled = $event.target.checked"
                                               class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                                    </label>
                                </div>
                                <div x-show="schedule['{{ $dayKey }}']?.enabled" class="flex gap-3">
                                    <div class="flex-1">
                                        <label class="text-xs text-gray-500 mb-1 block">Início</label>
                                        <input type="time"
                                               :value="schedule['{{ $dayKey }}']?.start"
                                               @change="schedule['{{ $dayKey }}'].start = $event.target.value"
                                               class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div class="flex-1">
                                        <label class="text-xs text-gray-500 mb-1 block">Fim</label>
                                        <input type="time"
                                               :value="schedule['{{ $dayKey }}']?.end"
                                               @change="schedule['{{ $dayKey }}'].end = $event.target.value"
                                               class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>

                {{-- Footer com botões --}}
                <div class="px-6 py-4 border-t border-gray-100 flex gap-2 shrink-0">
                    <button type="button" @click="closeModal()"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button x-show="step !== 'main'" type="button" @click="step = 'main'"
                        class="flex-1 rounded-xl border border-primary-300 py-2.5 text-sm font-medium text-primary-600 hover:bg-primary-50">
                        Voltar
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 flex items-center justify-center gap-2">
                        <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="submitting ? 'Salvando…' : 'Salvar'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function profsPage() {
    const defaultStart = '{{ sprintf('%02d:00', $tenant->agenda_start_hour ?? 8) }}';
    const defaultEnd   = '{{ sprintf('%02d:00', $tenant->agenda_end_hour ?? 22) }}';

    function buildDefaultSchedule() {
        const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        const sched = {};
        days.forEach(d => {
            sched[d] = { enabled: d !== 'sunday', start: defaultStart, end: defaultEnd };
        });
        return sched;
    }

    return {
        modalOpen: false,
        editing: null,
        step: 'main',
        serviceCommissions: {},
        schedule: buildDefaultSchedule(),

        openCreate() {
            this.editing = null;
            this.step = 'main';
            this.serviceCommissions = {};
            this.schedule = buildDefaultSchedule();
            this.modalOpen = true;
        },

        openEdit(prof, serviceComms) {
            this.editing = prof;
            this.step = 'main';
            this.serviceCommissions = serviceComms || {};
            this.schedule = (prof.work_schedule && Object.keys(prof.work_schedule).length)
                ? prof.work_schedule
                : buildDefaultSchedule();
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.editing = null;
        },

        toggleServiceComm(serviceId, enabled) {
            if (enabled) {
                if (!this.serviceCommissions[serviceId]) {
                    this.serviceCommissions[serviceId] = { enabled: true, pct: '0' };
                } else {
                    this.serviceCommissions[serviceId].enabled = true;
                }
            } else {
                if (this.serviceCommissions[serviceId]) {
                    delete this.serviceCommissions[serviceId];
                }
            }
            this.serviceCommissions = { ...this.serviceCommissions };
        },

        setServicePct(serviceId, pct) {
            if (this.serviceCommissions[serviceId]) {
                this.serviceCommissions[serviceId].pct = pct;
            }
        },
    }
}
</script>
@endsection
