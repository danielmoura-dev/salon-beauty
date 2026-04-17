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

    {{-- Assinatura (accordion) --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden" x-data="{ open: false }">

        {{-- Cabeçalho clicável --}}
        <button @click="open = !open" type="button"
                class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                @if ($subscription?->isActive())
                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    <span class="font-semibold text-gray-900">Assinatura</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700">Ativa</span>
                @elseif ($tenant->plan_status === 'trial')
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                    <span class="font-semibold text-gray-900">Assinatura</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Trial</span>
                @else
                    <span class="h-2 w-2 rounded-full bg-red-400"></span>
                    <span class="font-semibold text-gray-900">Assinatura</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700">Inativa</span>
                @endif
            </div>
            <svg :class="open ? 'rotate-180' : ''"
                 class="h-4 w-4 text-gray-400 transition-transform duration-200"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Corpo expansível --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="border-t border-gray-100 p-5 space-y-4">

            {{-- Bloco de informações do plano --}}
            <div class="rounded-xl bg-gray-50 border border-gray-200 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-gray-800">Plano Full</span>
                    <span class="text-sm font-bold text-gray-900">R$ 57,90 / mês</span>
                </div>

                <div class="space-y-1 text-xs text-gray-500">
                    <div class="flex justify-between">
                        <span>Status</span>
                        @if ($subscription?->cancel_at_period_end)
                            <span class="font-semibold text-amber-600">Cancelamento agendado</span>
                        @elseif ($subscription?->isActive())
                            <span class="font-semibold text-green-600">Ativa</span>
                        @elseif ($tenant->plan_status === 'trial')
                            @php $dLeft = (int) today()->diffInDays($tenant->trial_ends_at->copy()->startOfDay(), false); @endphp
                            <span class="font-semibold text-amber-600">Trial — {{ $dLeft > 0 ? "expira em {$dLeft} dias" : 'expira hoje' }}</span>
                        @else
                            <span class="font-semibold text-red-500">Inativa</span>
                        @endif
                    </div>

                    @if ($subscription?->isActive())
                        <div class="flex justify-between">
                            <span>Forma de pagamento</span>
                            <span class="font-semibold text-gray-700">
                                {{ $subscription->gateway === 'stripe' ? 'Cartão de crédito (automático)' : 'PIX (renovação manual)' }}
                            </span>
                        </div>
                        @if ($subscription->current_period_end)
                            <div class="flex justify-between">
                                @if ($subscription->cancel_at_period_end)
                                    <span class="text-amber-600">Acesso até</span>
                                    <span class="font-semibold text-amber-600">{{ $subscription->current_period_end->format('d/m/Y') }}</span>
                                @else
                                    <span>{{ $subscription->gateway === 'stripe' ? 'Próxima cobrança' : 'Válido até' }}</span>
                                    <span class="font-semibold text-gray-700">{{ $subscription->current_period_end->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Ações --}}
            @if ($subscription?->gateway === 'stripe' && in_array($subscription->status, ['active', 'past_due']))
                <a href="{{ route('subscription.portal') }}"
                   class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 hover:bg-gray-50 transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-800">Gerenciar assinatura</p>
                        <p class="text-xs text-gray-400">Trocar cartão, ver faturas, cancelar</p>
                    </div>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
                <a href="{{ route('subscription.index') }}"
                   class="block text-center text-xs text-gray-400 hover:text-gray-600 underline underline-offset-2">
                    Trocar para PIX
                </a>

            @elseif ($subscription?->gateway === 'mercadopago' && $subscription->status === 'active')
                <a href="{{ route('subscription.index') }}"
                   class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 hover:bg-gray-50 transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-800">Renovar assinatura</p>
                        <p class="text-xs text-gray-400">Gerar novo QR PIX para o próximo mês</p>
                    </div>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
                <a href="{{ route('subscription.index') }}"
                   class="block text-center text-xs text-gray-400 hover:text-gray-600 underline underline-offset-2">
                    Trocar para cartão de crédito
                </a>

            @else
                <a href="{{ route('subscription.index') }}"
                   class="flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                    {{ $tenant->plan_status === 'trial' ? 'Ver planos e assinar' : 'Reativar assinatura' }}
                </a>
            @endif
        </div>
    </div>

</div>
@endsection