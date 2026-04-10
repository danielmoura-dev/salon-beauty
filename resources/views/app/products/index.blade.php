@extends('layouts.app')
@section('title', 'Produtos — Gestão Beauty')

@section('content')
<div class="space-y-5" x-data="productsPage()">

    <div class="flex items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Produtos</h1>
            <p class="text-sm text-gray-400">{{ $products->count() }} cadastrados</p>
        </div>
        <div class="ml-auto flex gap-2">
            <button @click="openCategory()"
                class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                + Categoria
            </button>
            <button @click="openCreate()"
                class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                + Novo Produto
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @forelse ($products as $product)
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                @if ($product->photo)
                    <img src="{{ Storage::url($product->photo) }}"
                         class="h-32 w-full object-cover" alt="">
                @else
                    <div class="h-20 w-full bg-gray-50 flex items-center justify-center text-3xl">📦</div>
                @endif
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $product->name }}</p>
                            @if ($product->brand)
                                <p class="text-xs text-gray-400">{{ $product->brand }}</p>
                            @endif
                        </div>
                        @if ($product->for_sale)
                            <span class="shrink-0 text-xs bg-green-50 text-green-600 rounded-full px-2 py-0.5 font-medium">
                                R$ {{ number_format($product->price, 2, ',', '.') }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs text-gray-400">{{ $product->category?->name ?? 'Sem categoria' }}</span>
                        <div class="flex gap-2">
                            <button @click="openEdit({{ $product->toJson() }})"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                                Editar
                            </button>
                            <form method="POST" action="{{ route('products.destroy', $product) }}"
                                  onsubmit="return confirm('Remover {{ $product->name }}?')">
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
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16 text-gray-400">
                <div class="text-4xl mb-3">📦</div>
                <p class="font-medium">Nenhum produto cadastrado</p>
            </div>
        @endforelse
    </div>

    {{-- Modal Produto --}}
    <x-modal name="product" title="Produto">
        <form :action="editing ? `/products/${editing.id}` : '{{ route('products.store') }}'"
              method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Nome *">
                    <input type="text" name="name" :value="editing?.name" required
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
                <x-form-field label="Marca">
                    <input type="text" name="brand" :value="editing?.brand"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
            </div>

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

            <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                <span class="text-sm font-medium text-gray-700">Disponível para venda</span>
                <input type="checkbox" name="for_sale" value="1"
                       :checked="editing?.for_sale"
                       x-model="forSale"
                       class="rounded border-gray-300 text-rose-500 focus:ring-rose-500">
            </label>

            <div x-show="forSale" class="grid grid-cols-2 gap-3">
                <x-form-field label="Preço (R$)">
                    <input type="number" name="price" :value="editing?.price" step="0.01" min="0"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
                <x-form-field label="Comissão (%)">
                    <input type="number" name="commission_pct" :value="editing?.commission_pct ?? 0"
                           min="0" max="100" step="0.5"
                           class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
            </div>

            <x-form-field label="Foto (opcional)">
                <input type="file" name="photo" accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0
                           file:bg-rose-50 file:px-3 file:py-1.5 file:text-rose-600 file:text-sm">
            </x-form-field>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-product')"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    Salvar
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Categoria --}}
    <x-modal name="product-category" title="Nova Categoria">
        <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="type" value="product">
            <x-form-field label="Nome da categoria *">
                <input type="text" name="name" required autofocus
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
            </x-form-field>
            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-product-category')"
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
function productsPage() {
    return {
        editing: null,
        forSale: false,
        openCreate() { this.editing = null; this.forSale = false; this.$dispatch('open-modal-product'); },
        openEdit(p)   { this.editing = p; this.forSale = p.for_sale; this.$dispatch('open-modal-product'); },
        openCategory(){ this.$dispatch('open-modal-product-category'); },
    }
}
</script>
@endsection