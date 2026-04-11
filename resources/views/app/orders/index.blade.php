@extends('layouts.app')
@section('title', 'Comandas — Gestão Beauty')

@section('content')
<div class="space-y-5" x-data="ordersPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Comandas</h1>
            <p class="text-sm text-gray-400">{{ $date->translatedFormat('d \d\e F \d\e Y') }}</p>
        </div>
        <button @click="showNew = true"
            class="sm:ml-auto rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
            + Nova Comanda
        </button>
    </div>

    {{-- Resumo financeiro --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Total do dia</p>
            <p class="text-xl font-bold text-gray-900">R$ {{ number_format($summary['total'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Serviços</p>
            <p class="text-xl font-bold text-primary-600">R$ {{ number_format($summary['services'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Produtos</p>
            <p class="text-xl font-bold text-blue-600">R$ {{ number_format($summary['products'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="flex items-center gap-2">
        <form method="GET" class="flex gap-2 flex-wrap">
            <input type="date" name="date" value="{{ $date->toDateString() }}"
                onchange="this.form.submit()"
                class="rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">

            @foreach (['open' => 'Abertas', 'closed' => 'Fechadas', 'all' => 'Todas'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition-colors
                           {{ $status === $val
                               ? 'bg-primary-600 text-white'
                               : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </form>
    </div>

    {{-- Lista de comandas --}}
    <div class="space-y-3">
        @forelse ($orders as $order)
            <div @click="$dispatch('open-order-modal', { orderId: '{{ $order->id }}' })"
               class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 shadow-sm
                      px-4 py-3.5 hover:border-primary-200 transition-colors cursor-pointer">

                {{-- Avatar cliente --}}
                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center
                            text-primary-500 font-bold shrink-0">
                    {{ strtoupper(substr($order->client->name, 0, 1)) }}
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900">{{ $order->client->name }}</p>
                    <p class="text-sm text-gray-400">
                        {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                        · {{ $order->created_at->format('H:i') }}
                    </p>
                </div>

                {{-- Total + Status --}}
                <div class="text-right shrink-0">
                    <p class="font-bold text-gray-900">
                        R$ {{ number_format($order->total, 2, ',', '.') }}
                    </p>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                 {{ $order->status === 'open'
                                     ? 'bg-amber-100 text-amber-700'
                                     : 'bg-green-100 text-green-700' }}">
                        {{ $order->status === 'open' ? 'Aberta' : 'Fechada' }}
                    </span>
                </div>

                <svg class="h-4 w-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400">
                <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                <p class="font-medium">Nenhuma comanda encontrada</p>
            </div>
        @endforelse
    </div>

    {{-- Modal Nova Comanda --}}
    <div x-show="showNew" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="showNew = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl p-6 space-y-4"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">

            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Nova Comanda</h2>
                <button @click="showNew = false" class="text-gray-400 hover:text-gray-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <form action="{{ route('orders.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select name="client_id" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Selecione…</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->name }}
                                @if ($client->balance < 0)
                                    (deve R$ {{ number_format(abs($client->balance), 2, ',', '.') }})
                                @elseif ($client->balance > 0)
                                    (crédito R$ {{ number_format($client->balance, 2, ',', '.') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="notes" rows="2"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="showNew = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                        Abrir Comanda
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function ordersPage() {
    return { showNew: false }
}
</script>

<x-order-modal />

@endsection