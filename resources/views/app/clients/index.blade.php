@extends('layouts.app')
@section('title', 'Clientes — Gestão Beauty')

@section('content')
<div class="space-y-5" x-data="clientsPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Clientes</h1>
            <p class="text-sm text-gray-400">{{ $clients->total() }} cadastrados</p>
        </div>
        <div class="sm:ml-auto">
            <button @click="openCreate()"
                class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-xl bg-rose-600
                       px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition-colors">
                + Novo Cliente
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="flex flex-col sm:flex-row gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Buscar por nome ou telefone…"
            class="flex-1 rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
        <select name="filter"
            class="rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
            <option value="">Todos</option>
            <option value="debtors" @selected(request('filter') === 'debtors')>Inadimplentes</option>
            <option value="credits" @selected(request('filter') === 'credits')>Com crédito</option>
        </select>
        <button type="submit"
            class="rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-200">
            Filtrar
        </button>
    </form>

    {{-- Lista --}}
    <div class="space-y-2">
        @forelse ($clients as $client)
            <div class="flex items-center gap-4 rounded-2xl bg-white border border-gray-100 px-4 py-3 shadow-sm">

                {{-- Avatar --}}
                @if ($client->photo)
                    <img src="{{ Storage::url($client->photo) }}"
                         class="h-11 w-11 rounded-full object-cover shrink-0" alt="">
                @else
                    <div class="h-11 w-11 rounded-full bg-rose-100 flex items-center justify-center
                                text-rose-500 font-semibold text-sm shrink-0">
                        {{ strtoupper(substr($client->name, 0, 1)) }}
                    </div>
                @endif

                {{-- Info --}}
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900 truncate">{{ $client->name }}</p>
                    <p class="text-sm text-gray-400 truncate">{{ $client->phone ?? $client->email ?? '—' }}</p>
                </div>

                {{-- Saldo --}}
                @if ($client->balance != 0)
                    <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full
                                 {{ $client->hasDebt() ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">
                        {{ $client->hasDebt() ? '-' : '+' }}{{ $client->formattedBalance() }}
                    </span>
                @endif

                {{-- Ações --}}
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="openEdit({{ $client->toJson() }})"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                        Editar
                    </button>
                    <form method="POST" action="{{ route('clients.destroy', $client) }}"
                          onsubmit="return confirm('Remover {{ $client->name }}?')">
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
        @empty
            <div class="text-center py-16 text-gray-400">
                <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                <p class="font-medium">Nenhum cliente encontrado</p>
                <p class="text-sm mt-1">Cadastre seu primeiro cliente clicando em "Novo Cliente"</p>
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    <div>{{ $clients->links() }}</div>

    {{-- Modal Criar/Editar --}}
    <x-modal name="client" :title="'Novo Cliente'">
        <form :action="editingClient ? `/clients/${editingClient.id}` : '{{ route('clients.store') }}'"
              method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <template x-if="editingClient">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-form-field label="Nome *">
                <input type="text" name="name" :value="editingClient?.name" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
            </x-form-field>

            <div class="grid grid-cols-2 gap-3">
                <x-form-field label="Telefone">
                    <input type="tel" name="phone" :value="editingClient?.phone"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
                <x-form-field label="Aniversário">
                    <input type="date" name="birthday" :value="editingClient?.birthday?.substring(0, 10)"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
                </x-form-field>
            </div>

            <x-form-field label="E-mail">
                <input type="email" name="email" :value="editingClient?.email"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500">
            </x-form-field>

            <x-form-field label="Observações">
                <textarea name="notes" rows="2"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-rose-500 focus:border-rose-500"
                    x-text="editingClient?.notes"></textarea>
            </x-form-field>

            <x-form-field label="Foto (opcional)">
                <input type="file" name="photo" accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0
                           file:bg-rose-50 file:px-3 file:py-1.5 file:text-rose-600 file:text-sm
                           hover:file:bg-rose-100">
            </x-form-field>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-client')"
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

</div>

<script>
function clientsPage() {
    return {
        editingClient: null,
        openCreate() {
            this.editingClient = null;
            this.$dispatch('open-modal-client');
        },
        openEdit(client) {
            this.editingClient = client;
            this.$dispatch('open-modal-client');
        },
    }
}
</script>
@endsection