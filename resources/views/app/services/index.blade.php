@extends('layouts.app')
@section('title', 'Serviços — Gestão Beauty')

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
                class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
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
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                                Editar
                            </button>
                            <form method="POST" action="{{ route('services.destroy', $service) }}"
                                  onsubmit="return confirm('Remover {{ $service->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
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
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
            </x-form-field>

            <x-form-field label="Categoria">
                <select name="category_id"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                    <option value="">Sem categoria</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" :selected="editing?.category_id === '{{ $cat->id }}'">
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </x-form-field>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Preço (R$) *">
                    <input type="number" name="price" :value="editing?.price ?? ''" step="0.01" min="0" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
                <x-form-field label="Duração (min) *">
                    <input type="number" name="duration_min" :value="editing?.duration_min ?? 60" min="5" step="5" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
            </div>

            <x-form-field label="Comissão padrão (%)">
                <input type="number" name="commission_pct" :value="editing?.commission_pct ?? 0"
                       min="0" max="100" step="0.5"
                       class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
            </x-form-field>

            <x-form-field label="Observações">
                <textarea name="notes" rows="2"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500"
                    x-text="editing?.notes"></textarea>
            </x-form-field>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-service')"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" :disabled="submitting"
                    class="flex-1 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60 flex items-center justify-center gap-2">
                    <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="submitting ? 'Salvando…' : 'Salvar'"></span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Nova Categoria --}}
    <x-modal name="category" title="Nova Categoria">
        <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="type" value="service">
            <x-form-field label="Nome da categoria *">
                <input type="text" name="name" required autofocus
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
            </x-form-field>
            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-category')"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    Criar
                </button>
            </div>
        </form>
    </x-modal>

</div>

<script>
function servicesPage() {
    return {
        editing: null,
        openCreate() { this.editing = null; this.$dispatch('open-modal-service'); },
        openEdit(s)   { this.editing = s;    this.$dispatch('open-modal-service'); },
        openCategory(){ this.$dispatch('open-modal-category'); },
    }
}
</script>
@endsection