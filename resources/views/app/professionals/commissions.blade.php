@extends('layouts.app')
@section('title', 'Comissões — Salon Beauty')

@section('content')
<div class="space-y-5" x-data="commissionsPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <a href="{{ route('professionals') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 mb-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Profissionais
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Comissões</h1>
        </div>
        <div class="sm:ml-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <select x-model="filterProfId"
                class="rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500 py-2 pl-3 pr-8">
                <option value="">Todos os profissionais</option>
                @foreach ($professionals as $prof)
                    <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-medium transition-colors">A Pagar</button>
                <button @click="tab = 'history'" :class="tab === 'history' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-medium transition-colors">Histórico</button>
            </div>
        </div>
    </div>

    {{-- Aba: A Pagar --}}
    <div x-show="tab === 'pending'" class="space-y-2">
        @forelse ($professionals as $prof)
            <div x-show="filterProfId === '' || filterProfId === '{{ $prof->id }}'"
                 class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 px-4 py-3 shadow-sm">
                @if ($prof->photo)
                    <img src="{{ Storage::url($prof->photo) }}" class="h-11 w-11 rounded-full object-cover shrink-0" alt="">
                @else
                    <div class="h-11 w-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold shrink-0">
                        {{ strtoupper(substr($prof->name, 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900">{{ $prof->name }}</p>
                    <p class="text-xs text-gray-400">{{ $prof->specialty ?? 'Sem especialidade' }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="font-bold text-lg {{ $prof->pending_commission > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        R$ {{ number_format($prof->pending_commission, 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-400">pendente</p>
                </div>
                <button @click="openPay({{ json_encode(['id' => $prof->id, 'name' => $prof->name, 'photo' => $prof->photo, 'specialty' => $prof->specialty]) }})"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold
                           {{ $prof->pending_commission > 0 ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-100 text-gray-400 hover:bg-gray-200' }}
                           transition-colors">
                    Ver / Pagar
                </button>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400"><p class="font-medium">Nenhum profissional cadastrado</p></div>
        @endforelse
    </div>

    {{-- Aba: Histórico --}}
    <div x-show="tab === 'history'" class="space-y-2">
        @forelse ($history as $payment)
            <div x-show="filterProfId === '' || filterProfId === '{{ $payment->professional_id }}'"
                 class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 px-4 py-3 shadow-sm">
                @if ($payment->professional->photo)
                    <img src="{{ Storage::url($payment->professional->photo) }}" class="h-10 w-10 rounded-full object-cover shrink-0" alt="">
                @else
                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold text-sm shrink-0">
                        {{ strtoupper(substr($payment->professional->name, 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900">{{ $payment->professional->name }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $payment->period_start ? $payment->period_start->format('d/m/Y').' até ' : 'Acumulado até ' }}
                        {{ $payment->period_end->format('d/m/Y') }}
                    </p>
                </div>
                <div class="text-right shrink-0 hidden sm:block text-xs text-gray-400 space-y-0.5">
                    <p>Serviços: R$ {{ number_format($payment->total_services, 2, ',', '.') }}</p>
                    <p>Produtos: R$ {{ number_format($payment->total_products, 2, ',', '.') }}</p>
                    @if ($payment->total_vouchers > 0)
                        <p class="text-red-500">Vales: -R$ {{ number_format($payment->total_vouchers, 2, ',', '.') }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="font-bold text-green-600">R$ {{ number_format($payment->net_amount, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ $payment->created_at->format('d/m/Y') }}</p>
                </div>
                <form method="POST"
                      id="form-cancel-commission-{{ $payment->id }}"
                      action="{{ route('professionals.commissions.cancel', $payment) }}">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                        @click="$dispatch('open-confirm', {
                            formId:  'form-cancel-commission-{{ $payment->id }}',
                            title:   'Cancelar pagamento',
                            message: 'Os valores voltarão para pendente.',
                            label:   'Cancelar pagamento'
                        })"
                        class="shrink-0 text-red-400 hover:text-red-600 transition-colors p-1"
                        title="Cancelar pagamento">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400"><p class="font-medium">Nenhum pagamento registrado ainda</p></div>
        @endforelse
    </div>

    {{-- Modal: Ver / Pagar --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl max-h-[90vh] flex flex-col" @click.stop>

            {{-- Header --}}
            <div class="flex items-center gap-3 px-6 pt-6 pb-4 border-b border-gray-100 shrink-0">
                <template x-if="prof && prof.photo">
                    <img :src="`/storage/${prof.photo}`" class="h-11 w-11 rounded-full object-cover shrink-0">
                </template>
                <template x-if="prof && !prof.photo">
                    <div class="h-11 w-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold shrink-0"
                         x-text="prof.name?.charAt(0).toUpperCase()"></div>
                </template>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900" x-text="prof?.name"></p>
                    <p class="text-xs text-gray-400" x-text="prof?.specialty || 'Sem especialidade'"></p>
                </div>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Período --}}
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50 shrink-0">
                <div class="flex flex-col sm:flex-row gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="periodType" value="accumulated" @change="loadDetail()" class="text-primary-500">
                        <span class="text-sm text-gray-700">Acumulado até hoje</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="periodType" value="custom" class="text-primary-500">
                        <span class="text-sm text-gray-700">Período personalizado</span>
                    </label>
                </div>
                <div x-show="periodType === 'custom'" class="flex gap-3 mt-3">
                    <div class="flex-1">
                        <label class="text-xs text-gray-500 mb-1 block">De</label>
                        <input type="date" x-model="dateFrom" @change="loadDetail()"
                               class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div class="flex-1">
                        <label class="text-xs text-gray-500 mb-1 block">Até</label>
                        <input type="date" x-model="dateTo" @change="loadDetail()"
                               class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            {{-- Conteúdo scrollável --}}
            <div class="overflow-y-auto flex-1 px-6 py-4 space-y-3">
                <template x-if="loading">
                    <div class="text-center py-8 text-gray-400">
                        <svg class="h-6 w-6 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </div>
                </template>

                <template x-if="!loading && detail">
                    <div class="space-y-3">

                        {{-- Serviços --}}
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <button @click="toggleSection('services')" type="button"
                                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors">
                                <span class="font-medium text-sm text-gray-700">Serviços</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-green-600" x-text="fmt(selectedTotal('service'))"></span>
                                    <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openSections.services ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </button>
                            <div x-show="openSections.services" class="divide-y divide-gray-100">
                                <template x-if="detail.services.length === 0">
                                    <p class="px-4 py-3 text-sm text-gray-400">Nenhum serviço no período</p>
                                </template>
                                <template x-for="item in detail.services" :key="item.id">
                                    <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" :value="item.id" x-model="selectedItemIds"
                                               class="rounded border-gray-300 text-green-500 focus:ring-green-400 shrink-0">
                                        <span class="text-gray-400 text-xs w-20 shrink-0" x-text="item.date"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-800 text-sm truncate" x-text="item.description"></p>
                                            <p class="text-xs text-gray-400" x-text="item.client + ' · ' + item.payment_method"></p>
                                        </div>
                                        <span class="text-xs text-gray-400 shrink-0" x-text="item.commission_pct + '%'"></span>
                                        <span class="font-semibold text-green-600 text-sm shrink-0" x-text="fmt(item.value)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Produtos --}}
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <button @click="toggleSection('products')" type="button"
                                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors">
                                <span class="font-medium text-sm text-gray-700">Produtos</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-green-600" x-text="fmt(selectedTotal('product'))"></span>
                                    <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openSections.products ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </button>
                            <div x-show="openSections.products" class="divide-y divide-gray-100">
                                <template x-if="detail.products.length === 0">
                                    <p class="px-4 py-3 text-sm text-gray-400">Nenhum produto no período</p>
                                </template>
                                <template x-for="item in detail.products" :key="item.id">
                                    <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" :value="item.id" x-model="selectedItemIds"
                                               class="rounded border-gray-300 text-green-500 focus:ring-green-400 shrink-0">
                                        <span class="text-gray-400 text-xs w-20 shrink-0" x-text="item.date"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-800 text-sm truncate" x-text="item.description"></p>
                                            <p class="text-xs text-gray-400" x-text="item.client + ' · ' + item.payment_method"></p>
                                        </div>
                                        <span class="text-xs text-gray-400 shrink-0" x-text="item.commission_pct + '%'"></span>
                                        <span class="font-semibold text-green-600 text-sm shrink-0" x-text="fmt(item.value)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Outros --}}
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <button @click="toggleSection('others')" type="button"
                                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors">
                                <span class="font-medium text-sm text-gray-700">Outros</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-green-600" x-text="fmt(selectedTotal('other'))"></span>
                                    <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openSections.others ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </button>
                            <div x-show="openSections.others" class="divide-y divide-gray-100">
                                <template x-if="detail.others.length === 0">
                                    <p class="px-4 py-3 text-sm text-gray-400">Nenhum item no período</p>
                                </template>
                                <template x-for="item in detail.others" :key="item.id">
                                    <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" :value="item.id" x-model="selectedItemIds"
                                               class="rounded border-gray-300 text-green-500 focus:ring-green-400 shrink-0">
                                        <span class="text-gray-400 text-xs w-20 shrink-0" x-text="item.date"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-800 text-sm truncate" x-text="item.description"></p>
                                            <p class="text-xs text-gray-400" x-text="item.client + ' · ' + item.payment_method"></p>
                                        </div>
                                        <span class="text-xs text-gray-400 shrink-0" x-text="item.commission_pct + '%'"></span>
                                        <span class="font-semibold text-green-600 text-sm shrink-0" x-text="fmt(item.value)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Vales --}}
                        <div class="rounded-xl border border-amber-200 overflow-hidden">
                            <button @click="toggleSection('vouchers')" type="button"
                                class="w-full flex items-center justify-between px-4 py-3 bg-amber-50 hover:bg-amber-100 transition-colors">
                                <span class="font-medium text-sm text-amber-800">Desconto de Vales</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-red-500" x-text="'- ' + fmt(selectedVoucherTotal())"></span>
                                    <svg class="h-4 w-4 text-amber-400 transition-transform" :class="openSections.vouchers ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </button>
                            <div x-show="openSections.vouchers" class="divide-y divide-amber-100">
                                <template x-if="detail.vouchers.length === 0">
                                    <p class="px-4 py-3 text-sm text-gray-400">Nenhum vale pendente</p>
                                </template>
                                <template x-for="v in detail.vouchers" :key="v.id">
                                    <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-amber-50">
                                        <input type="checkbox" :value="v.id" x-model="selectedVoucherIds"
                                               class="rounded border-gray-300 text-amber-500 focus:ring-amber-400 shrink-0">
                                        <span class="text-gray-400 text-xs w-20 shrink-0" x-text="v.issued_at"></span>
                                        <p class="flex-1 text-sm text-gray-800" x-text="v.description"></p>
                                        <span class="font-semibold text-red-500 text-sm" x-text="'- ' + fmt(v.amount)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Total --}}
                        <div class="rounded-xl bg-gray-900 text-white px-5 py-4 flex items-center justify-between">
                            <span class="font-semibold">Total a pagar</span>
                            <span class="text-2xl font-bold text-green-400" x-text="fmt(netTotal())"></span>
                        </div>

                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-100 px-6 py-4 shrink-0">
                <form :action="`/professionals/${prof?.id}/commissions/pay`" method="POST"
                      x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="period_type" :value="periodType">
                    <input type="hidden" name="date_from" :value="dateFrom">
                    <input type="hidden" name="date_to" :value="dateTo || new Date().toISOString().split('T')[0]">

                    {{-- IDs selecionados --}}
                    <template x-for="id in selectedItemIds" :key="id">
                        <input type="hidden" name="item_ids[]" :value="id">
                    </template>
                    <template x-for="id in selectedVoucherIds" :key="id">
                        <input type="hidden" name="voucher_ids[]" :value="id">
                    </template>

                    <div class="flex gap-3">
                        <input type="text" name="notes" placeholder="Observação (opcional)"
                               class="flex-1 rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <button type="submit" :disabled="submitting || selectedItemIds.length === 0"
                            class="shrink-0 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white
                                   hover:bg-green-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                            <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Registrar Pagamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function commissionsPage() {
    return {
        tab: 'pending',
        filterProfId: '',
        modalOpen: false,
        prof: null,
        periodType: 'accumulated',
        dateFrom: '',
        dateTo: new Date().toISOString().split('T')[0],
        loading: false,
        detail: null,
        openSections: { services: false, products: false, others: false, vouchers: false },
        selectedItemIds: [],
        selectedVoucherIds: [],

        async openPay(prof) {
            this.prof = prof;
            this.periodType = 'accumulated';
            this.dateFrom = '';
            this.dateTo = new Date().toISOString().split('T')[0];
            this.openSections = { services: false, products: false, others: false, vouchers: false };
            this.detail = null;
            this.selectedItemIds = [];
            this.selectedVoucherIds = [];
            this.modalOpen = true;
            await this.loadDetail();
        },

        closeModal() { this.modalOpen = false; this.prof = null; this.detail = null; },

        toggleSection(name) { this.openSections[name] = !this.openSections[name]; },

        async loadDetail() {
            if (!this.prof) return;
            this.loading = true;
            const params = new URLSearchParams({
                period_type: this.periodType,
                date_from: this.dateFrom,
                date_to: this.dateTo || new Date().toISOString().split('T')[0],
            });
            const res = await fetch(`/professionals/${this.prof.id}/commissions/detail?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            this.detail = await res.json();

            // Seleciona todos por padrão
            this.selectedItemIds = [
                ...this.detail.services.map(i => i.id),
                ...this.detail.products.map(i => i.id),
                ...this.detail.others.map(i => i.id),
            ];
            this.selectedVoucherIds = this.detail.vouchers.map(v => v.id);
            this.loading = false;
        },

        allItems() {
            if (!this.detail) return [];
            return [...this.detail.services, ...this.detail.products, ...this.detail.others];
        },

        selectedTotal(type) {
            if (!this.detail) return 0;
            return this.detail[type === 'service' ? 'services' : type === 'product' ? 'products' : 'others']
                .filter(i => this.selectedItemIds.includes(i.id))
                .reduce((sum, i) => sum + i.value, 0);
        },

        selectedVoucherTotal() {
            if (!this.detail) return 0;
            return this.detail.vouchers
                .filter(v => this.selectedVoucherIds.includes(v.id))
                .reduce((sum, v) => sum + v.amount, 0);
        },

        netTotal() {
            return Math.max(0,
                this.selectedTotal('service') +
                this.selectedTotal('product') +
                this.selectedTotal('other') -
                this.selectedVoucherTotal()
            );
        },

        fmt(value) {
            return 'R$ ' + Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    }
}
</script>
@endsection
