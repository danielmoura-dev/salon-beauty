@extends('layouts.app')
@section('title', 'Configurações — Salon Beauty')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <h1 class="text-2xl font-bold text-gray-900">Configurações</h1>

    {{-- Dados do estabelecimento --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Dados do estabelecimento</h2>
        </div>
        <form method="POST" action="{{ route('settings.profile') }}"
              enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf

            @if ($tenant->logo)
                <div class="flex items-center gap-4">
                    <img src="{{ Storage::url($tenant->logo) }}"
                         class="h-16 w-16 rounded-2xl object-cover" alt="Logo">
                    <span class="text-sm text-gray-400">Logo atual</span>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do estabelecimento</label>
                <input type="text" name="name" value="{{ $tenant->name }}" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail do estabelecimento</label>
                <input type="email" value="{{ $tenant->email }}" readonly
                    class="w-full rounded-xl border-gray-200 bg-gray-50 text-gray-500 text-sm cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone / WhatsApp</label>
                <input type="tel" name="phone" value="{{ $tenant->phone }}"
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo (opcional)</label>
                <input type="file" name="logo" accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0
                           file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600 hover:file:bg-primary-100">
            </div>
            <button type="submit"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                Salvar dados
            </button>
        </form>
    </div>

    {{-- Alterar senha --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Alterar senha</h2>
        </div>
        <form method="POST" action="{{ route('settings.password') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha atual</label>
                <input type="password" name="current_password" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500
                           @error('current_password') border-red-400 @enderror">
                @error('current_password')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                <input type="password" name="password" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500
                           @error('password') border-red-400 @enderror">
                @error('password')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
                <input type="password" name="password_confirmation" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            </div>
            <button type="submit"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                Alterar senha
            </button>
        </form>
    </div>

    {{-- Taxas e configurações avançadas --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Taxas e preferências</h2>
        </div>
        <form method="POST" action="{{ route('settings.advanced') }}" class="p-5 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Taxa cartão crédito (%)</label>
                    <input type="number" name="credit_card_fee"
                           value="{{ $tenant->credit_card_fee }}" step="0.1" min="0" max="20"
                           class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Taxa cartão débito (%)</label>
                    <input type="number" name="debit_card_fee"
                           value="{{ $tenant->debit_card_fee }}" step="0.1" min="0" max="20"
                           class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Início da agenda</label>
                    <select name="agenda_start_hour"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        @for ($h = 0; $h <= 23; $h++)
                            <option value="{{ $h }}" @selected($tenant->agenda_start_hour == $h)>
                                {{ sprintf('%02d:00', $h) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fim da agenda</label>
                    <select name="agenda_end_hour"
                        class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        @for ($h = 1; $h <= 23; $h++)
                            <option value="{{ $h }}" @selected(($tenant->agenda_end_hour ?? 22) == $h)>
                                {{ sprintf('%02d:00', $h) }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="space-y-3 pt-1">
                <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Permitir telefone duplicado</p>
                        <p class="text-xs text-gray-400">Dois clientes com o mesmo número</p>
                    </div>
                    <input type="checkbox" name="allow_duplicate_phone" value="1"
                           @checked($tenant->allow_duplicate_phone)
                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                </label>

                <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Exibir comandas pendentes de dias anteriores</p>
                        <p class="text-xs text-gray-400">Mostra junto com as do dia atual</p>
                    </div>
                    <input type="checkbox" name="show_pending_orders" value="1"
                           @checked($tenant->show_pending_orders)
                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                </label>
            </div>

            <button type="submit"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                Salvar preferências
            </button>
        </form>
    </div>

    {{-- Assinatura --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Assinatura</h2>
        </div>
        <div class="p-5">
            @if ($subscription && $subscription->isActive())
                <div class="flex items-center gap-3 mb-4">
                    <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                    <span class="text-sm font-semibold text-green-700">Plano Full — Ativo</span>
                    <span class="text-xs text-gray-400 ml-auto">
                        via {{ $subscription->gateway === 'stripe' ? 'Cartão' : 'Pix' }}
                        · renova {{ $subscription->current_period_end?->format('d/m/Y') }}
                    </span>
                </div>
                @if ($subscription->gateway === 'stripe')
                    <form method="POST" action="{{ route('subscription.cancel') }}">
                        @csrf
                        <button type="submit"
                            onclick="return confirm('Cancelar assinatura? Você terá acesso até o fim do período pago.')"
                            class="text-sm text-red-500 hover:underline">
                            Cancelar assinatura
                        </button>
                    </form>
                @endif
            @else
                <p class="text-sm text-gray-500 mb-4">
                    Você está no período de teste.
                    @if (auth()->user()->tenant->trial_ends_at)
                        @php $dLeft = (int) today()->diffInDays(auth()->user()->tenant->trial_ends_at->copy()->startOfDay(), false); @endphp
                        Expira em <strong>{{ $dLeft > 0 ? "{$dLeft} dias" : 'hoje' }}</strong>.
                    @endif
                </p>
                <a href="{{ route('subscription.index') }}"
                   class="inline-block rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                    Ver planos e assinar
                </a>
            @endif
        </div>
    </div>

</div>
@endsection