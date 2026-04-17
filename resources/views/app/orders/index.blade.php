@extends('layouts.app')
@section('title', 'Comandas — Salon Beauty')

@section('content')
<div class="space-y-5" x-data="ordersPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Comandas</h1>

        {{-- Navegação de data --}}
        <div class="flex items-center gap-1 sm:mx-auto">
            @php
                $prevDate = $date->copy()->subDay();
                $nextDate = $date->copy()->addDay();
                $prevUrl  = $prevDate->isToday() ? route('orders') : route('orders', ['date' => $prevDate->toDateString()]);
                $nextUrl  = $nextDate->isToday() ? route('orders') : route('orders', ['date' => $nextDate->toDateString()]);
            @endphp
            <a href="{{ $prevUrl }}" class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                </svg>
            </a>
            <div class="text-center px-2">
                <p class="text-sm font-semibold text-gray-800">{{ $date->translatedFormat('d \d\e F') }}</p>
                @if ($date->isToday())
                    <p class="text-xs text-primary-500 font-medium leading-none">Hoje</p>
                @else
                    <p class="text-xs text-gray-400 leading-none">{{ $date->translatedFormat('l') }}</p>
                @endif
            </div>
            <a href="{{ $nextUrl }}" class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>

        <button @click="openNew()"
            class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
            + Nova Comanda
        </button>
    </div>

    {{-- Resumo --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Abertas</p>
            <p class="text-2xl font-bold text-amber-500">{{ $summary['openCount'] }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Fechadas</p>
            <p class="text-2xl font-bold text-green-600">{{ $summary['closedCount'] }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Valor esperado</p>
            <p class="text-lg font-bold text-gray-500">R$ {{ number_format($summary['expectedTotal'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Valor atual</p>
            <p class="text-lg font-bold text-gray-900">R$ {{ number_format($summary['actualTotal'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filtros + tab Vendas do dia --}}
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="activeTab = 'open'; showVendas = false"
            :class="activeTab === 'open' && !showVendas ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
            class="rounded-xl px-4 py-2 text-sm font-medium transition-colors">
            Abertas <span x-text="openCount > 0 ? '(' + openCount + ')' : ''" class="opacity-70"></span>
        </button>
        <button @click="activeTab = 'closed'; showVendas = false"
            :class="activeTab === 'closed' && !showVendas ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
            class="rounded-xl px-4 py-2 text-sm font-medium transition-colors">
            Fechadas
        </button>
        <button @click="activeTab = 'all'; showVendas = false"
            :class="activeTab === 'all' && !showVendas ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
            class="rounded-xl px-4 py-2 text-sm font-medium transition-colors">
            Todas
        </button>
        <button @click="showVendas = !showVendas"
                :class="showVendas ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
                class="rounded-xl px-4 py-2 text-sm font-medium transition-colors">
            Vendas do dia
        </button>
    </div>

    {{-- PAINEL: Vendas do dia --}}
    <div x-show="showVendas" x-cloak class="space-y-4">

        {{-- Sub-tabs --}}
        <div class="flex gap-2">
            <button @click="vendasTab = 'item'"
                    :class="vendasTab === 'item' ? 'bg-gray-900 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                Por item
            </button>
            <button @click="vendasTab = 'payment'"
                    :class="vendasTab === 'payment' ? 'bg-gray-900 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition-colors">
                Por pagamento
            </button>
        </div>

        {{-- Por item --}}
        <div x-show="vendasTab === 'item'" class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <p class="font-semibold text-gray-800">Vendas por tipo</p>
                <p class="text-xs text-gray-400">Somente comandas fechadas</p>
            </div>
            @php
                $salesTotal = array_sum($summary['salesByType']);
                $salesRows  = [
                    'Serviços'  => $summary['salesByType']['services'],
                    'Produtos'  => $summary['salesByType']['products'],
                    'Outros'    => $summary['salesByType']['others'],
                ];
            @endphp
            @foreach ($salesRows as $label => $value)
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-50 last:border-0">
                    <span class="text-sm text-gray-700">{{ $label }}</span>
                    <span class="font-semibold text-gray-900 text-sm">R$ {{ number_format($value, 2, ',', '.') }}</span>
                </div>
            @endforeach
            <div class="flex items-center justify-between px-5 py-3.5 bg-gray-50 border-t border-gray-100">
                <span class="font-semibold text-gray-700 text-sm">Total</span>
                <span class="font-bold text-gray-900">R$ {{ number_format($salesTotal, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- Por pagamento --}}
        <div x-show="vendasTab === 'payment'" class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <p class="font-semibold text-gray-800">Receitas por forma de pagamento</p>
                <p class="text-xs text-gray-400">Somente comandas fechadas</p>
            </div>
            @if (count($summary['salesByPayment']) > 0)
                @foreach ($summary['salesByPayment'] as $method => $value)
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-50 last:border-0">
                        <span class="text-sm text-gray-700">{{ \App\Models\Payment::$methodLabels[$method] ?? $method }}</span>
                        <span class="font-semibold text-green-700 text-sm">R$ {{ number_format($value, 2, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="flex items-center justify-between px-5 py-3.5 bg-gray-50 border-t border-gray-100">
                    <span class="font-semibold text-gray-700 text-sm">Total recebido</span>
                    <span class="font-bold text-gray-900">R$ {{ number_format(array_sum($summary['salesByPayment']), 2, ',', '.') }}</span>
                </div>
            @else
                <div class="px-5 py-10 text-center text-sm text-gray-400">
                    Nenhum pagamento registrado hoje.
                </div>
            @endif
        </div>
    </div>

    {{-- Lista de comandas --}}
    <div x-show="!showVendas" class="space-y-3">

        {{-- Comandas criadas na sessão (sem reload) --}}
        <template x-for="o in newOrders" :key="o.id">
            <div @click="$dispatch('open-order-modal', { orderId: o.id })"
                 x-show="activeTab === 'all' || activeTab === 'open'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 shadow-sm
                        px-4 py-3.5 hover:border-primary-200 transition-colors cursor-pointer">
                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold shrink-0"
                     x-text="o.client?.name?.charAt(0)?.toUpperCase()"></div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900" x-text="o.client?.name"></p>
                    <p class="text-sm text-gray-400" x-text="(o.items?.length ?? 0) + ' itens · agora'"></p>
                </div>
                <div class="text-right shrink-0">
                    <p class="font-bold text-gray-900">R$ 0,00</p>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Aberta</span>
                </div>
                <svg class="h-4 w-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </div>
        </template>

        @forelse ($orders as $order)
            <div x-data="{
                    cardStatus: '{{ $order->status }}',
                    cardTotal: {{ $order->total }},
                    cardItems: {{ $order->items->count() }},
                    init() {
                        window.addEventListener('order-updated', e => {
                            if (e.detail.order?.id === '{{ $order->id }}') {
                                this.cardStatus = e.detail.order.status ?? this.cardStatus;
                                this.cardTotal  = e.detail.order.total  ?? this.cardTotal;
                                this.cardItems  = e.detail.order.items?.length ?? this.cardItems;
                            }
                        });
                    }
                 }"
               @click="$dispatch('open-order-modal', { orderId: '{{ $order->id }}' })"
               x-show="!deletedIds.has('{{ $order->id }}') && cardStatus !== 'cancelled' && (activeTab === 'all' || activeTab === cardStatus)"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100 scale-100"
               x-transition:leave-end="opacity-0 scale-95"
               class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 shadow-sm
                      px-4 py-3.5 hover:border-primary-200 transition-colors cursor-pointer">

                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center
                            text-primary-500 font-bold shrink-0">
                    {{ strtoupper(substr($order->client->name, 0, 1)) }}
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900">{{ $order->client->name }}</p>
                    <p class="text-sm text-gray-400">
                        <span x-text="cardItems + (cardItems === 1 ? ' item' : ' itens')"></span>
                        · {{ $order->created_at->format('H:i') }}
                    </p>
                </div>

                <div class="text-right shrink-0">
                    <p class="font-bold text-gray-900"
                       x-text="'R$ ' + cardTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></p>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                          :class="cardStatus === 'open' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'"
                          x-text="cardStatus === 'open' ? 'Aberta' : 'Fechada'"></span>
                </div>

                <svg class="h-4 w-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </div>
        @empty
        @endforelse

        {{-- Estado vazio client-side (quando filtro não tem resultados) --}}
        <div x-show="{{ $orders->count() }} === 0 || (activeTab === 'open' && openCount === 0) || (activeTab === 'closed' && closedCount === 0)"
             class="text-center py-16 text-gray-400">
            <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
            <p class="font-medium">Nenhuma comanda encontrada</p>
        </div>
    </div>

    {{-- Modal Nova Comanda --}}
    <div x-show="showNew" x-cloak
         class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
         style="z-index:100; display:none">
        <div class="absolute inset-0 bg-black/40" @click="closeNew()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 pt-7 pb-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Nova Comanda</h2>
                <button @click="closeNew()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-6 pt-4 pb-6 space-y-4">

                {{-- ESTADO: cliente já selecionado --}}
                <div x-show="selectedClient">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Cliente selecionado</p>
                    <div class="flex items-center gap-3 rounded-xl bg-primary-50 border border-primary-200 px-3 py-2.5">
                        <div class="h-9 w-9 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold text-sm shrink-0"
                             x-text="selectedClient?.name?.charAt(0)?.toUpperCase()"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate" x-text="selectedClient?.name"></p>
                            <p class="text-xs"
                               :class="selectedClient?.balance < 0 ? 'text-red-500' : selectedClient?.balance > 0 ? 'text-green-600' : 'text-gray-400'"
                               x-text="selectedClient?.balance < 0
                                   ? 'Deve R$ ' + Math.abs(selectedClient.balance).toFixed(2).replace('.', ',')
                                   : selectedClient?.balance > 0
                                       ? 'Crédito R$ ' + Number(selectedClient.balance).toFixed(2).replace('.', ',')
                                       : selectedClient?.phone || 'Sem telefone'"></p>
                        </div>
                        <button type="button"
                            @click="selectedClient = null; selectedClientId = null; $nextTick(() => fetchClients(true))"
                            class="text-xs text-primary-600 hover:text-primary-800 font-medium shrink-0 ml-1">
                            Trocar
                        </button>
                    </div>
                </div>

                {{-- ESTADO: selecionando cliente --}}
                <div x-show="!selectedClient" class="space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Selecionar cliente *</p>
                    <input type="text" x-model="clientSearch"
                           @input="onSearchInput()"
                           placeholder="Buscar por nome ou telefone…"
                           class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">

                    {{-- Caixa fixa com scroll interno --}}
                    <div class="h-64 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50"
                         @scroll="onListScroll($event)">
                        <div class="px-1 py-1">
                            <template x-for="client in visibleClients()" :key="client.id">
                                <button type="button" @click="selectClient(client)"
                                    class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-white hover:shadow-sm transition-all text-left">
                                    <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold text-sm shrink-0"
                                         x-text="client.name?.charAt(0)?.toUpperCase()"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 text-sm truncate" x-text="client.name"></p>
                                        <p class="text-xs"
                                           :class="client.balance < 0 ? 'text-red-500' : client.balance > 0 ? 'text-green-600' : 'text-gray-400'"
                                           x-text="client.balance < 0
                                               ? 'Deve R$ ' + Math.abs(client.balance).toFixed(2).replace('.', ',')
                                               : client.balance > 0
                                                   ? 'Crédito R$ ' + Number(client.balance).toFixed(2).replace('.', ',')
                                                   : client.phone || 'Sem telefone'"></p>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </button>
                            </template>

                            {{-- Spinner: carregando lista inicial --}}
                            <div x-show="clientLoading" class="flex justify-center py-4">
                                <svg class="animate-spin h-5 w-5 text-primary-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </div>

                            <p x-show="!clientLoading && filteredClients().length === 0"
                               class="text-center text-sm text-gray-400 py-8">
                                Nenhum cliente encontrado.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Observações --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea x-model="newOrderNotes" rows="2"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>

                {{-- Botões --}}
                <div class="flex gap-2">
                    <button type="button" @click="closeNew()"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="createOrder()" :disabled="!selectedClientId || creatingOrder"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span x-show="!creatingOrder">Abrir Comanda</span>
                        <span x-show="creatingOrder">Aguarde…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function ordersPage() {
    return {
        showNew:          false,
        creatingOrder:    false,
        newOrderNotes:    '',
        newOrders:        [],
        showVendas:       false,
        vendasTab:        'item',
        activeTab:        'open',
        openCount:        {{ $openCount }},
        closedCount:      {{ $closedCount }},
        deletedIds:       new Set(),

        init() {
            window.addEventListener('order-deleted', (e) => {
                this.deletedIds = new Set([...this.deletedIds, e.detail.orderId]);
                // Atualiza contagens ao deletar
                const status = e.detail.orderStatus;
                if (status === 'open')   this.openCount   = Math.max(0, this.openCount - 1);
                if (status === 'closed') this.closedCount = Math.max(0, this.closedCount - 1);
            });
        },

        // picker de cliente
        allClients:       [],   // lista completa carregada uma vez
        clientSearch:     '',
        displayLimit:     50,   // quantos nós no DOM por vez
        clientLoading:    false,
        selectedClient:   null,
        selectedClientId: null,

        // Filtra in-memory (instantâneo)
        filteredClients() {
            const q = this.clientSearch.trim().toLowerCase();
            if (!q) return this.allClients;
            return this.allClients.filter(c =>
                c.name.toLowerCase().includes(q) ||
                (c.phone && c.phone.replace(/\D/g, '').includes(q.replace(/\D/g, '')))
            );
        },

        // Slice dos filtrados para limitar nós no DOM
        visibleClients() {
            return this.filteredClients().slice(0, this.displayLimit);
        },

        // Busca todos os clientes uma única vez
        async loadAllClients() {
            if (this.allClients.length || this.clientLoading) return;
            this.clientLoading = true;
            try {
                const res  = await fetch('/clients/search?q=&page=all', {
                    headers: { Accept: 'application/json' },
                });
                const json = await res.json();
                this.allClients = json.data;
            } catch (_) {
                // silencioso
            } finally {
                this.clientLoading = false;
            }
        },

        onSearchInput() {
            this.displayLimit = 50; // reset ao buscar
        },

        // Expande o slice ao chegar perto do fim
        onListScroll(event) {
            const el = event.target;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 80) {
                const total = this.filteredClients().length;
                if (this.displayLimit < total) {
                    this.displayLimit = Math.min(this.displayLimit + 50, total);
                }
            }
        },

        openNew() {
            this.showNew = true;
            this.loadAllClients();
        },

        selectClient(client) {
            this.selectedClient   = client;
            this.selectedClientId = client.id;
        },

        closeNew() {
            this.showNew          = false;
            this.selectedClient   = null;
            this.selectedClientId = null;
            this.clientSearch     = '';
            this.newOrderNotes    = '';
            this.displayLimit     = 50;
        },

        async createOrder() {
            if (!this.selectedClientId || this.creatingOrder) return;
            this.creatingOrder = true;
            try {
                const res = await fetch('{{ route('orders.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ client_id: this.selectedClientId, notes: this.newOrderNotes }),
                });
                if (res.ok) {
                    const order = await res.json();
                    this.closeNew();
                    this.openCount++;
                    this.newOrders.unshift(order);
                    window.dispatchEvent(new CustomEvent('open-order-modal', { detail: { orderId: order.id } }));
                }
            } finally {
                this.creatingOrder = false;
            }
        },
    }
}
</script>

<x-order-modal />

@endsection
