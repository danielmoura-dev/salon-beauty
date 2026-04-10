@extends('layouts.app')
@section('title', 'Despesas — Gestão Beauty')

@section('content')
<div class="space-y-5" x-data="expensesPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Despesas</h1>
            <p class="text-sm text-gray-400">{{ $month->translatedFormat('F \d\e Y') }}</p>
        </div>
        <div class="sm:ml-auto flex gap-2">
            <button @click="showCategory = true"
                class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                + Categoria
            </button>
            <button @click="openCreate()"
                class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                + Nova Despesa
            </button>
        </div>
    </div>

    {{-- Navegação de mês --}}
    <div class="flex items-center gap-2">
        <a href="{{ route('expenses', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"
           class="rounded-xl p-2 hover:bg-gray-100 text-gray-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>
        <span class="flex-1 text-center text-sm font-semibold text-gray-700">
            {{ $month->translatedFormat('F Y') }}
        </span>
        <a href="{{ route('expenses', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}"
           class="rounded-xl p-2 hover:bg-gray-100 text-gray-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
    </div>

    {{-- Resumo --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Total</p>
            <p class="text-lg font-bold text-gray-900">R$ {{ number_format($summary['total'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Pagas</p>
            <p class="text-lg font-bold text-green-600">R$ {{ number_format($summary['paid'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">Pendentes</p>
            <p class="text-lg font-bold text-amber-600">R$ {{ number_format($summary['pending'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Lista --}}
    <div class="space-y-2">
        @forelse ($expenses as $expense)
            <div class="flex items-center gap-3 rounded-2xl bg-white border border-gray-100 shadow-sm px-4 py-3.5
                        {{ $expense->is_paid ? 'opacity-70' : '' }}">

                {{-- Toggle pago --}}
                <form method="POST" action="{{ route('expenses.toggle', $expense) }}">
                    @csrf
                    <button type="submit"
                        class="h-6 w-6 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors
                               {{ $expense->is_paid
                                   ? 'bg-green-500 border-green-500 text-white'
                                   : 'border-gray-300 hover:border-green-400' }}">
                        @if ($expense->is_paid)
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        @endif
                    </button>
                </form>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 text-sm {{ $expense->is_paid ? 'line-through text-gray-400' : '' }}">
                        {{ $expense->description }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $expense->category?->name ?? 'Sem categoria' }}
                        · Vence {{ $expense->due_date->format('d/m') }}
                        · {{ \App\Models\Expense::$paymentTypeLabels[$expense->payment_type] }}
                    </p>
                </div>

                <p class="font-bold text-gray-900 text-sm shrink-0">
                    R$ {{ number_format($expense->amount, 2, ',', '.') }}
                </p>

                {{-- Ações --}}
                <div class="flex gap-1 shrink-0">
                    <button @click="openEdit({{ $expense->toJson() }})"
                        class="rounded-lg px-2.5 py-1.5 bg-rose-50 text-rose-600 text-xs font-semibold hover:bg-rose-100 transition-colors">
                        Editar
                    </button>
                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                          onsubmit="return confirm('Remover despesa?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="rounded-lg px-2.5 py-1.5 bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition-colors">
                            Excluir
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400">
                <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                <p class="font-medium">Nenhuma despesa neste mês</p>
            </div>
        @endforelse
    </div>

    {{-- Modal Despesa --}}
    <div x-show="showForm" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="showForm = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">

            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Editar Despesa' : 'Nova Despesa'"></h2>
                <button @click="showForm = false" class="text-gray-400 hover:text-gray-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <form :action="editing ? `/expenses/${editing.id}` : '{{ route('expenses.store') }}'"
                  method="POST" class="space-y-4">
                @csrf
                <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                    <input type="text" name="description" :value="editing?.description" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$) *</label>
                        <input type="number" name="amount" :value="editing?.amount" step="0.01" min="0.01" required
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vencimento *</label>
                        <input type="date" name="due_date"
                               :value="editing?.due_date?.substring(0,10)"
                               required
                               class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select name="category_id"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                        <option value="">Sem categoria</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" :selected="editing?.category_id === '{{ $cat->id }}'">
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div x-show="!editing">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Forma de pagamento</label>
                    <select name="payment_type" x-model="paymentType"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                        <option value="one_time">À vista</option>
                        <option value="installment">Parcelado</option>
                        <option value="recurring">Recorrente</option>
                    </select>
                </div>

                <div x-show="paymentType === 'installment' && !editing">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de parcelas</label>
                    <input type="number" name="installments" min="2" max="60" value="2"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </div>

                <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                    <span class="text-sm font-medium text-gray-700">Já foi paga</span>
                    <input type="checkbox" name="is_paid" value="1"
                           :checked="editing?.is_paid"
                           class="rounded border-gray-300 text-rose-500 focus:ring-rose-500">
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="notes" rows="2"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500"
                        x-text="editing?.notes"></textarea>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="button" @click="showForm = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Categoria --}}
    <div x-show="showCategory" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="showCategory = false"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl p-6 space-y-4" @click.stop>
            <h2 class="text-lg font-semibold text-gray-900">Nova Categoria</h2>
            <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="expense">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="name" required autofocus
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="showCategory = false"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                        Criar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function expensesPage() {
    return {
        showForm: false,
        showCategory: false,
        editing: null,
        paymentType: 'one_time',
        openCreate() {
            this.editing = null;
            this.paymentType = 'one_time';
            this.showForm = true;
        },
        openEdit(expense) {
            this.editing = expense;
            this.paymentType = expense.payment_type;
            this.showForm = true;
        },
    }
}
</script>
@endsection