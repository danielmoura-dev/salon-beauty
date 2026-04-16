@extends('layouts.app')
@section('title', 'Serviços — Salon Beauty')

@section('content')
<div class="space-y-5" x-data="servicesPage()">

    <div class="flex items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Serviços</h1>
            <p class="text-sm text-gray-400">{{ $services->count() }} cadastrados</p>
        </div>
        <div class="ml-auto flex gap-2">
            <button @click="openCategory()"
                class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                + Categoria
            </button>
            <button @click="openCreate()"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                + Novo Serviço
            </button>
        </div>
    </div>

    {{-- Agrupado por categoria --}}
    @forelse ($services->groupBy(fn($s) => $s->category?->name ?? 'Sem categoria') as $catName => $group)
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">{{ $catName }}</h2>
            <div class="space-y-2">
                @foreach ($group as $service)
                    <div class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 px-4 py-3 shadow-sm">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900">{{ $service->name }}</p>
                                @unless ($service->active)
                                    <span class="text-xs bg-gray-100 text-gray-400 rounded-full px-2 py-0.5">Inativo</span>
                                @endunless
                            </div>
                            <p class="text-sm text-gray-400">
                                {{ $service->formattedDuration() }} ·
                                {{ $service->formattedPrice() }}
                                @if ($service->commission_pct > 0)
                                    · {{ $service->commission_pct }}% comissão
                                @endif
                            </p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button @click="openEdit({{ $service->toJson() }})"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium bg-accent-50 text-accent-600 hover:bg-accent-100 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                                Editar
                            </button>
                            <form id="del-service-{{ $service->id }}"
                                  method="POST" action="{{ route('services.destroy', $service) }}">
                                @csrf @method('DELETE')
                                <button type="button"
                                    @click="$dispatch('open-confirm', { formId: 'del-service-{{ $service->id }}', message: 'Remover {{ addslashes($service->name) }}? Esta ação não pode ser desfeita.' })"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center py-16 text-gray-400">
            <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 11-5.196 3 3 3 0 015.196-3zm1.536-.887a2.165 2.165 0 001.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863l2.077-1.199m0-3.328a4.323 4.323 0 012.068-1.379l5.325-1.628a4.5 4.5 0 012.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0010.607 12m3.736 0l7.794 4.5-.802.215a4.5 4.5 0 01-2.48-.043l-5.326-1.629a4.324 4.324 0 01-2.068-1.379M14.343 12l-2.882 1.664"/></svg>
            <p class="font-medium">Nenhum serviço cadastrado</p>
        </div>
    @endforelse

    {{-- Modal Serviço --}}
    <x-modal name="service" title="Serviço">
        <form :action="editing ? `/services/${editing.id}` : '{{ route('services.store') }}'"
              method="POST" class="space-y-4"
              x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

            <x-form-field label="Nome *">
                <input type="text" name="name" :value="editing?.name" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

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

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Preço (R$) *">
                    <input type="number" name="price" :value="editing?.price ?? ''" step="0.01" min="0" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </x-form-field>
                <x-form-field label="Duração (min) *">
                    <input type="number" name="duration_min" :value="editing?.duration_min ?? 60" min="5" step="5" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </x-form-field>
            </div>

            <x-form-field label="Comissão padrão (%)">
                <input type="number" name="commission_pct" :value="editing?.commission_pct ?? 0"
                       min="0" max="100" step="0.5"
                       class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

            <x-form-field label="Observações">
                <textarea name="notes" rows="2"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"
                    x-text="editing?.notes"></textarea>
            </x-form-field>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-service')"
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
    <x-modal name="category" title="Categorias de Serviço">
        <div class="space-y-5">

            {{-- Lista de categorias existentes --}}
            <div x-show="categories.length > 0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Categorias existentes</p>
                <div class="rounded-xl border border-gray-100 divide-y divide-gray-50 max-h-44 overflow-y-auto">
                    <template x-for="cat in categories" :key="cat.id">
                        <div class="flex items-center justify-between px-3 py-2.5">
                            <span class="text-sm font-medium text-gray-700" x-text="cat.name"></span>
                            <button type="button" @click="deleteCategory(cat)"
                                class="ml-2 shrink-0 text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                        <button type="button" @click="$dispatch('close-modal-category')"
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
function servicesPage() {
    return {
        editing: null,
        categories: @json($categories),
        selectedCategoryId: '',
        categoryFormName: '',
        categoryFormSaving: false,
        categoryFormError: '',

        openCreate() {
            this.editing = null;
            this.selectedCategoryId = '';
            this.$dispatch('open-modal-service');
        },

        openEdit(s) {
            this.editing = s;
            this.$nextTick(() => { this.selectedCategoryId = s.category_id || ''; });
            this.$dispatch('open-modal-service');
        },

        openCategory() {
            this.categoryFormName = '';
            this.categoryFormError = '';
            this.$dispatch('open-modal-category');
        },

        async deleteCategory(cat) {
            if (!confirm(`Remover a categoria "${cat.name}"?`)) return;
            const res = await fetch(`/categories/${cat.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    Accept: 'application/json',
                },
            });
            if (res.ok) {
                this.categories = this.categories.filter(c => c.id !== cat.id);
                if (this.selectedCategoryId === cat.id) this.selectedCategoryId = '';
            } else {
                const err = await res.json().catch(() => ({}));
                alert(err.message || 'Erro ao remover categoria.');
            }
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
                    body: JSON.stringify({ name: this.categoryFormName, type: 'service' }),
                });
                if (res.ok) {
                    const cat = await res.json();
                    this.categories.push({ id: cat.id, name: cat.name, type: cat.type });
                    this.categories.sort((a, b) => a.name.localeCompare(b.name, 'pt-BR'));
                    this.selectedCategoryId = cat.id;
                    this.categoryFormName = '';
                } else {
                    const err = await res.json().catch(() => ({}));
                    this.categoryFormError = err.message || 'Erro ao salvar.';
                }
            } catch { this.categoryFormError = 'Erro de conexão.'; }
            finally  { this.categoryFormSaving = false; }
        },
    }
}
</script>
@endsection
