@extends('layouts.app')
@section('title', 'Comanda — ' . $order->client->name)

@section('content')
<div class="space-y-5 max-w-2xl mx-auto" x-data="orderPage()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('orders') }}" class="hover:text-primary-600">Comandas</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">{{ $order->client->name }}</span>
    </div>

    {{-- Header da comanda --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $order->client->name }}</h1>
                <p class="text-sm text-gray-400 mt-0.5">
                    Aberta em {{ $order->created_at->format('d/m/Y \à\s H:i') }}
                </p>
                @if ($order->client->balance != 0)
                    <p class="text-sm mt-1 font-medium
                               {{ $order->client->balance < 0 ? 'text-red-500' : 'text-green-600' }}">
                        {{ $order->client->balance < 0 ? 'Deve' : 'Crédito' }}:
                        R$ {{ number_format(abs($order->client->balance), 2, ',', '.') }}
                    </p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="text-xs font-semibold px-3 py-1 rounded-full
                             {{ $order->status === 'open' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                    {{ $order->status === 'open' ? 'Aberta' : 'Fechada' }}
                </span>
                @if ($order->status === 'closed')
                    <form method="POST" action="{{ route('orders.reopen', $order) }}">
                        @csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-primary-600 hover:underline">
                            Reabrir
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Itens da comanda --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Itens</h2>
            @if ($order->status === 'open')
                <button @click="showAddItem = true"
                    class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white hover:bg-primary-700">
                    + Adicionar
                </button>
            @endif
        </div>

        @forelse ($order->items as $item)
            <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-50 last:border-0">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm">{{ $item->description }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $item->qty }}x R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                        @if ($item->professional)
                            · {{ $item->professional->name }}
                        @endif
                        @if ($item->has_commission && $item->commission_pct > 0)
                            · {{ $item->commission_pct }}% comissão
                        @endif
                    </p>
                </div>
                <p class="font-semibold text-gray-900 text-sm shrink-0">
                    R$ {{ number_format($item->subtotal(), 2, ',', '.') }}
                </p>
                @if ($order->status === 'open')
                    <form id="del-item-{{ $item->id }}"
                          method="POST"
                          action="{{ route('orders.items.remove', [$order, $item]) }}">
                        @csrf @method('DELETE')
                        <button type="button"
                            @click="$dispatch('open-confirm', { formId: 'del-item-{{ $item->id }}', message: 'Remover este item da comanda?' })"
                            class="rounded-lg p-1.5 bg-red-50 text-red-500 hover:bg-red-100 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                Nenhum item adicionado.
            </div>
        @endforelse

        {{-- Total --}}
        <div class="flex items-center justify-between px-5 py-4 bg-gray-50 border-t border-gray-100">
            <span class="font-semibold text-gray-700">Total</span>
            <span class="text-xl font-bold text-gray-900">
                R$ {{ number_format($order->total, 2, ',', '.') }}
            </span>
        </div>
    </div>

    {{-- Pagamentos --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Pagamentos</h2>
            @if ($order->status === 'open')
                <button @click="showPayment = true"
                    class="rounded-xl bg-green-600 px-4 py-2 text-xs font-semibold text-white hover:bg-green-700">
                    + Receber
                </button>
            @endif
        </div>

        @forelse ($order->payments as $payment)
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">{{ $payment->methodLabel() }}</span>
                    @if ($payment->notes)
                        <span class="text-xs text-gray-400">· {{ $payment->notes }}</span>
                    @endif
                </div>
                <span class="font-semibold text-green-700 text-sm">
                    + R$ {{ number_format($payment->amount, 2, ',', '.') }}
                </span>
            </div>
        @empty
            <div class="px-5 py-6 text-center text-gray-400 text-sm">Nenhum pagamento registrado.</div>
        @endforelse

        {{-- Saldo --}}
        @if ($order->total > 0)
            @php $balance = $order->balance(); @endphp
            <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-t border-gray-100">
                <span class="text-sm font-medium text-gray-600">
                    {{ $balance >= 0 ? ($balance > 0 ? 'Troco / Crédito' : 'Pago') : 'Faltam' }}
                </span>
                <span class="font-bold {{ $balance < 0 ? 'text-red-500' : 'text-green-600' }}">
                    R$ {{ number_format(abs($balance), 2, ',', '.') }}
                </span>
            </div>
        @endif
    </div>

    {{-- Botão fechar comanda --}}
    @if ($order->status === 'open')
        <form id="form-fechar-comanda" method="POST" action="{{ route('orders.close', $order) }}">
            @csrf
            <button type="button"
                @click="$dispatch('open-confirm', { formId: 'form-fechar-comanda', title: 'Fechar comanda', message: 'Confirmar o fechamento desta comanda?', label: 'Fechar' })"
                class="w-full rounded-2xl bg-gray-900 py-4 text-white font-bold text-base hover:bg-gray-800 transition-colors">
                Fechar Comanda
            </button>
        </form>
    @endif

    {{-- ===== MODAL: ADICIONAR ITEM ===== --}}
    <div x-show="showAddItem" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="showAddItem = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">

            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Adicionar Item</h2>
                <button @click="showAddItem = false" class="text-gray-400 hover:text-gray-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <form action="{{ route('orders.items.add', $order) }}" method="POST" class="space-y-4">
                @csrf

                {{-- Tipo --}}
                <div class="flex gap-2">
                    @foreach (['service' => 'Serviço', 'product' => 'Produto', 'other' => 'Outro'] as $val => $label)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="type" value="{{ $val }}"
                                   x-model="itemType" class="peer sr-only">
                            <div class="rounded-xl border-2 py-2 text-center text-sm font-medium transition-colors
                                        peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700
                                        border-gray-200 text-gray-500 hover:border-gray-300">
                                {{ $label }}
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Serviço rápido --}}
                <div x-show="itemType === 'service'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preencher a partir de:</label>
                    <select @change="fillFromService($event)"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Selecione um serviço…</option>
                        @foreach ($services as $svc)
                            <option value="{{ $svc->id }}"
                                    data-price="{{ $svc->price }}"
                                    data-name="{{ $svc->name }}"
                                    data-commission="{{ $svc->commission_pct }}">
                                {{ $svc->name }} — R$ {{ number_format($svc->price, 2, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Produto rápido --}}
                <div x-show="itemType === 'product'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preencher a partir de:</label>
                    <select @change="fillFromProduct($event)"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Selecione um produto…</option>
                        @foreach ($products as $prod)
                            <option value="{{ $prod->id }}"
                                    data-price="{{ $prod->price }}"
                                    data-name="{{ $prod->name }}"
                                    data-commission="{{ $prod->commission_pct }}">
                                {{ $prod->name }} — R$ {{ number_format($prod->price, 2, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Descrição --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                    <input type="text" name="description" x-model="itemDesc" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Qtd</label>
                        <input type="number" name="qty" x-model="itemQty" min="1" value="1"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor unitário (R$)</label>
                        <input type="number" name="unit_price" x-model="itemPrice" step="0.01" min="0" required
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                {{-- Profissional + Comissão --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Profissional</label>
                    <select name="professional_id"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Sem profissional</option>
                        @foreach ($professionals as $prof)
                            <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                    <span class="text-sm font-medium text-gray-700">Gera comissão</span>
                    <input type="checkbox" name="has_commission" value="1" x-model="hasCommission"
                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                </label>

                <div x-show="hasCommission">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comissão (%)</label>
                    <input type="number" name="commission_pct" x-model="itemCommission"
                           min="0" max="100" step="0.5"
                           class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>

                {{-- Preview do subtotal --}}
                <div class="rounded-xl bg-gray-50 px-4 py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-500">Subtotal</span>
                    <span class="font-bold text-gray-900"
                          x-text="'R$ ' + (itemQty * itemPrice).toFixed(2).replace('.', ',')"></span>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showAddItem = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                        Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== MODAL: RECEBER PAGAMENTO ===== --}}
    <div x-show="showPayment" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="showPayment = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl p-6 space-y-4"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">

            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Receber Pagamento</h2>
                <button @click="showPayment = false" class="text-gray-400 hover:text-gray-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            {{-- Resumo rápido --}}
            <div class="rounded-xl bg-gray-50 p-3 text-sm flex justify-between">
                <span class="text-gray-500">Total da comanda</span>
                <span class="font-bold">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
            </div>
            <div class="rounded-xl bg-gray-50 p-3 text-sm flex justify-between">
                <span class="text-gray-500">Já recebido</span>
                <span class="font-bold text-green-700">R$ {{ number_format($order->totalPaid(), 2, ',', '.') }}</span>
            </div>

            <form action="{{ route('orders.payments.add', $order) }}" method="POST" class="space-y-4">
                @csrf

                {{-- Métodos de pagamento --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Forma de pagamento *</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (\App\Models\Payment::$methodLabels as $val => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="method" value="{{ $val }}" class="peer sr-only" required>
                                <div class="rounded-xl border-2 py-2 px-1 text-center text-xs font-medium transition-colors
                                            peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700
                                            border-gray-200 text-gray-500 hover:border-gray-300">
                                    {{ $label }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                           value="{{ number_format(max($order->total - $order->totalPaid(), 0), 2, '.', '') }}"
                           required
                           class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <input type="text" name="notes" placeholder="Ex: troco de R$50"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="button" @click="showPayment = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-green-600 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function orderPage() {
    const services = @json($services->keyBy('id'));
    const products = @json($products->keyBy('id'));

    return {
        showAddItem:   false,
        showPayment:   false,
        itemType:      'service',
        itemDesc:      '',
        itemQty:       1,
        itemPrice:     0,
        itemCommission: 0,
        hasCommission:  true,

        fillFromService(e) {
            const opt = e.target.selectedOptions[0];
            if (!opt.value) return;
            this.itemDesc       = opt.dataset.name;
            this.itemPrice      = parseFloat(opt.dataset.price);
            this.itemCommission = parseFloat(opt.dataset.commission || 0);
        },

        fillFromProduct(e) {
            const opt = e.target.selectedOptions[0];
            if (!opt.value) return;
            this.itemDesc       = opt.dataset.name;
            this.itemPrice      = parseFloat(opt.dataset.price || 0);
            this.itemCommission = parseFloat(opt.dataset.commission || 0);
        },
    }
}
</script>
@endsection