@extends('layouts.app')
@section('title', 'Despesas — Salon Beauty')

@section('content')
<div class="space-y-5" x-data="expensesPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Despesas</h1>

        {{-- Navegação de mês --}}
        <div class="flex items-center gap-1 sm:mx-auto" x-data="monthPicker({{ $month->month }}, {{ $month->year }})">
            <a href="{{ route('expenses', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"
               class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                </svg>
            </a>

            {{-- Botão que abre o picker --}}
            <div class="relative">
                <button @click="open = !open" @click.outside="open = false"
                    class="text-center px-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors">
                    <p class="text-sm font-semibold text-gray-800 capitalize">{{ $month->translatedFormat('F Y') }}</p>
                </button>

                {{-- Popover --}}
                <div x-show="open" x-transition
                    class="absolute left-1/2 -translate-x-1/2 top-full mt-2 z-50 bg-white border border-gray-200 rounded-2xl shadow-lg p-4 w-64"
                    @click.outside="open = false">

                    {{-- Seletor de ano --}}
                    <div class="flex items-center justify-between mb-3">
                        <button @click="pickerYear--" class="rounded-lg p-1 hover:bg-gray-100 text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                            </svg>
                        </button>
                        <span class="text-sm font-semibold text-gray-800" x-text="pickerYear"></span>
                        <button @click="pickerYear++" class="rounded-lg p-1 hover:bg-gray-100 text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Grid de meses --}}
                    <div class="grid grid-cols-3 gap-1">
                        <template x-for="(m, i) in months" :key="i">
                            <button
                                @click="navigate(i + 1)"
                                :class="(i + 1) === pickerMonth && pickerYear === currentYear
                                    ? 'bg-primary-600 text-white font-semibold'
                                    : 'text-gray-700 hover:bg-gray-100'"
                                class="rounded-xl py-1.5 text-xs transition-colors"
                                x-text="m">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <a href="{{ route('expenses', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}"
               class="rounded-xl p-2 hover:bg-gray-100 text-gray-500 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>

        <div class="flex gap-2">
            <button @click="openCategory()"
                class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                + Categoria
            </button>
            <button @click="openCreate()"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                + Nova Despesa
            </button>
        </div>
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
                        class="rounded-lg px-2.5 py-1.5 bg-primary-50 text-primary-600 text-xs font-semibold hover:bg-primary-100 transition-colors">
                        Editar
                    </button>
                    <form id="del-expense-{{ $expense->id }}"
                          method="POST" action="{{ route('expenses.destroy', $expense) }}">
                        @csrf @method('DELETE')
                        <button type="button"
                            @click="$dispatch('open-confirm', { formId: 'del-expense-{{ $expense->id }}', message: 'Remover esta despesa? Esta ação não pode ser desfeita.' })"
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
    <x-modal name="expense" title="Despesa">
        <form :action="editing ? `/expenses/${editing.id}` : '{{ route('expenses.store') }}'"
              method="POST" class="space-y-4"
              x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

            <x-form-field label="Descrição *">
                <input type="text" name="description" :value="editing?.description" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Valor (R$) *">
                    <input type="number" name="amount" :value="editing?.amount" step="0.01" min="0.01" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </x-form-field>
                <x-form-field label="Vencimento *">
                    <input type="date" name="due_date" :value="editing?.due_date?.substring(0,10)" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </x-form-field>
            </div>

            <x-form-field label="Categoria">
                <div class="flex gap-2">
                    <select name="category_id" x-model="selectedCategoryId"
                        class="flex-1 rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Sem categoria</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                    <button type="button" @click="openCategory()"
                        class="shrink-0 rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 whitespace-nowrap">
                        + Nova
                    </button>
                </div>
            </x-form-field>

            <x-form-field label="Forma de pagamento" x-show="!editing">
                <select name="payment_type" x-model="paymentType"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="one_time">À vista</option>
                    <option value="installment">Parcelado</option>
                    <option value="recurring">Recorrente</option>
                </select>
            </x-form-field>

            {{-- Badge read-only quando editando --}}
            <div x-show="editing" class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700">Forma de pagamento:</span>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600"
                    x-text="{one_time:'À vista', installment:'Parcelado', recurring:'Recorrente'}[paymentType] ?? paymentType"></span>
            </div>

            <x-form-field label="Número de parcelas" x-show="paymentType === 'installment' && !editing">
                <input type="number" name="installments" min="2" max="60" value="2"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

            <x-form-field label="Número de meses" x-show="paymentType === 'recurring' && !editing">
                <input type="number" name="months" min="2" max="120" value="12"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

            {{-- Cancelar próximas recorrências --}}
            <div x-show="editing && editing.recurrence_group_id"
                 class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-red-700">Cancelar recorrência</p>
                    <p class="text-xs text-red-400 mt-0.5">Exclui esta e todas as próximas ocorrências não pagas</p>
                </div>
                <button type="button" @click="cancelRecurrence(editing)"
                    class="shrink-0 rounded-xl bg-red-500 text-white text-xs font-semibold px-3 py-1.5 hover:bg-red-600 transition-colors">
                    Cancelar próximas
                </button>
            </div>

            <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                <span class="text-sm font-medium text-gray-700">Já foi paga</span>
                <input type="checkbox" name="is_paid" value="1"
                       :checked="editing?.is_paid"
                       class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
            </label>

            <x-form-field label="Observações">
                <textarea name="notes" rows="2"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"
                    x-text="editing?.notes"></textarea>
            </x-form-field>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-expense')"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" :disabled="submitting"
                    class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 flex items-center justify-center gap-2">
                    <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="submitting ? 'Salvando…' : 'Salvar'"></span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Categorias --}}
    <x-modal name="expense-category" title="Categorias de Despesa">
        <div class="space-y-5">

            {{-- Lista de categorias existentes --}}
            <div x-show="categories.length > 0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Categorias existentes</p>
                <div class="rounded-xl border border-gray-100 divide-y divide-gray-50 max-h-44 overflow-y-auto">
                    <template x-for="cat in categories" :key="cat.id">
                        <div class="flex items-center justify-between px-3 py-2.5">
                            <span class="text-sm font-medium text-gray-700" x-text="cat.name"></span>
                            <button type="button" @click="deleteCategory(cat.id)"
                                class="ml-2 shrink-0 text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Adicionar nova categoria --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Nova categoria</p>
                <div class="space-y-3">
                    <input type="text" x-model="categoryFormName" placeholder="Nome da categoria"
                        @keydown.enter.prevent="saveCategoryForm()"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    <p x-show="categoryFormError" x-text="categoryFormError" class="text-sm text-red-500"></p>
                    <div class="flex gap-2">
                        <button type="button" @click="$dispatch('close-modal-expense-category')"
                            class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Fechar
                        </button>
                        <button type="button" @click="saveCategoryForm()" :disabled="categoryFormSaving"
                            class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 flex items-center justify-center gap-2">
                            <svg x-show="categoryFormSaving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span x-text="categoryFormSaving ? 'Salvando…' : 'Adicionar'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>

</div>

<script>
function expensesPage() {
    return {
        editing:            null,
        paymentType:        'one_time',
        categories:         @json($categories),
        selectedCategoryId: '',
        categoryFormName:   '',
        categoryFormSaving: false,
        categoryFormError:  '',

        openCreate() {
            this.editing            = null;
            this.paymentType        = 'one_time';
            this.selectedCategoryId = '';
            this.$dispatch('open-modal-expense');
        },

        openEdit(expense) {
            this.editing     = expense;
            this.paymentType = expense.payment_type;
            this.$nextTick(() => { this.selectedCategoryId = expense.category_id || ''; });
            this.$dispatch('open-modal-expense');
        },

        openCategory() {
            this.categoryFormName  = '';
            this.categoryFormError = '';
            this.$dispatch('open-modal-expense-category');
        },

        async saveCategoryForm() {
            if (!this.categoryFormName.trim()) { this.categoryFormError = 'Informe o nome.'; return; }
            this.categoryFormSaving = true; this.categoryFormError = '';
            try {
                const res = await fetch('{{ route('categories.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({ name: this.categoryFormName, type: 'expense' }),
                });
                if (res.ok) {
                    const cat = await res.json();
                    this.categories.push({ id: cat.id, name: cat.name, type: cat.type });
                    this.categories.sort((a, b) => a.name.localeCompare(b.name, 'pt-BR'));
                    this.selectedCategoryId = cat.id;
                    this.categoryFormName   = '';
                } else {
                    const err = await res.json().catch(() => ({}));
                    this.categoryFormError = err.message || 'Erro ao salvar.';
                }
            } catch { this.categoryFormError = 'Erro de conexão.'; }
            finally  { this.categoryFormSaving = false; }
        },

        async cancelRecurrence(expense) {
            window.__confirmCallback = async () => {
                const res = await fetch(`/expenses/${expense.id}/cancel-recurrence`, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                });
                if (res.ok) {
                    this.$dispatch('close-modal-expense');
                    window.location.reload();
                }
            };
            window.dispatchEvent(new CustomEvent('open-confirm', { detail: {
                title:   'Cancelar recorrência',
                message: 'Isso vai excluir esta e todas as próximas ocorrências não pagas. Confirmar?',
                label:   'Cancelar recorrência',
            }}));
        },

        async deleteCategory(id) {
            window.__confirmCallback = async () => {
                const res = await fetch(`/categories/${id}`, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                });
                if (res.ok) {
                    this.categories = this.categories.filter(c => c.id !== id);
                    if (this.selectedCategoryId === id) this.selectedCategoryId = '';
                }
            };
            window.dispatchEvent(new CustomEvent('open-confirm', { detail: {
                title:   'Remover categoria',
                message: 'Remover esta categoria de despesa?',
                label:   'Remover',
            }}));
        },
    }
}

function monthPicker(currentMonth, currentYear) {
    return {
        open: false,
        pickerMonth: currentMonth,
        pickerYear: currentYear,
        currentYear: currentYear,
        months: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],

        navigate(month) {
            const y = String(this.pickerYear);
            const m = String(month).padStart(2, '0');
            window.location.href = `{{ url('/expenses') }}?month=${y}-${m}`;
        },
    }
}
</script>
@endsection