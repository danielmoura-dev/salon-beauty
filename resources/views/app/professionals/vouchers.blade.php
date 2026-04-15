@extends('layouts.app')
@section('title', 'Vales — Salon Beauty')

@section('content')
<div class="space-y-5" x-data="vouchersPage()">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <a href="{{ route('professionals') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 mb-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Profissionais
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Vales</h1>
        </div>
        <button @click="openCreate()"
            class="sm:ml-auto flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5
                   text-sm font-semibold text-white hover:bg-amber-600 transition-colors">
            + Emitir Vale
        </button>
    </div>

    {{-- Lista de profissionais com vales --}}
    @forelse ($professionals as $prof)
        @if ($prof->vouchers->isNotEmpty())
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">

                {{-- Header do profissional --}}
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-100">
                    @if ($prof->photo)
                        <img src="{{ Storage::url($prof->photo) }}" class="h-9 w-9 rounded-full object-cover shrink-0" alt="">
                    @else
                        <div class="h-9 w-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-500 font-bold text-sm shrink-0">
                            {{ strtoupper(substr($prof->name, 0, 1)) }}
                        </div>
                    @endif
                    <p class="font-semibold text-gray-900">{{ $prof->name }}</p>
                    <span class="ml-auto text-sm text-gray-400">
                        Total pendente:
                        <strong class="text-amber-600">
                            R$ {{ number_format($prof->vouchers->whereNull('commission_payment_id')->sum('amount'), 2, ',', '.') }}
                        </strong>
                    </span>
                </div>

                {{-- Lista de vales --}}
                <div class="divide-y divide-gray-100">
                    @foreach ($prof->vouchers as $voucher)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800">{{ $voucher->description }}</p>
                                <p class="text-xs text-gray-400">Emitido em {{ $voucher->issued_at->format('d/m/Y') }}</p>
                            </div>
                            <span class="text-sm font-bold text-amber-600 shrink-0">
                                R$ {{ number_format($voucher->amount, 2, ',', '.') }}
                            </span>
                            @if ($voucher->commission_payment_id)
                                <span class="text-xs bg-gray-100 text-gray-500 rounded-full px-2.5 py-0.5 shrink-0">Descontado</span>
                            @else
                                <span class="text-xs bg-amber-50 text-amber-600 rounded-full px-2.5 py-0.5 shrink-0">Pendente</span>
                                <form method="POST" action="{{ route('professionals.vouchers.destroy', $voucher) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        onclick="return confirm('Remover este vale?')"
                                        class="text-gray-300 hover:text-red-500 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

            </div>
        @endif
    @empty
        <div class="text-center py-16 text-gray-400">
            <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a3 3 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/>
            </svg>
            <p class="font-medium">Nenhum profissional com vales</p>
        </div>
    @endforelse

    {{-- Modal: Emitir Vale --}}
    <x-modal name="voucher" title="Emitir Vale">
        <form :action="selectedProf ? `/professionals/${selectedProf}/vouchers` : '#'"
              method="POST" class="space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf

            <x-form-field label="Profissional *">
                <select name="professional_id" x-model="selectedProf" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Selecione…</option>
                    @foreach ($professionals as $prof)
                        <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field label="Valor (R$) *">
                <input type="number" name="amount" step="0.01" min="0.01" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

            <x-form-field label="Descrição *">
                <input type="text" name="description" required placeholder="ex: Adiantamento, Material…"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

            <x-form-field label="Data de emissão *">
                <input type="date" name="issued_at" :value="today" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </x-form-field>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="$dispatch('close-modal-voucher')"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" :disabled="submitting || !selectedProf"
                    class="flex-1 rounded-xl bg-amber-500 py-2.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-60 flex items-center justify-center gap-2">
                    <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="submitting ? 'Salvando…' : 'Emitir Vale'"></span>
                </button>
            </div>
        </form>
    </x-modal>

</div>

<script>
function vouchersPage() {
    return {
        selectedProf: '',
        today: new Date().toISOString().split('T')[0],

        openCreate() {
            this.selectedProf = '';
            this.$dispatch('open-modal-voucher');
        },
    }
}
</script>
@endsection
