@extends('layouts.app')
@section('title', 'Assinatura — Salon Beauty')

@section('content')
<div class="max-w-lg mx-auto space-y-6">

    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">Plano Full</h1>
        <p class="text-gray-500 mt-1">Acesso completo a todos os módulos do Salon Beauty</p>
    </div>

    {{-- ── Assinatura Stripe ATIVA ─────────────────────────────── --}}
    @if ($subscription?->gateway === 'stripe' && in_array($subscription->status, ['active', 'past_due']))
        <div class="rounded-2xl border-2 {{ $subscription->status === 'active' ? 'border-green-400 bg-green-50' : 'border-amber-400 bg-amber-50' }} p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Status da assinatura</p>
                    @if ($subscription->status === 'active')
                        <p class="text-lg font-bold text-green-700 flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                            Ativa — Cobrança automática mensal
                        </p>
                    @else
                        <p class="text-lg font-bold text-amber-700 flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                            Pagamento pendente
                        </p>
                    @endif
                </div>
                <svg class="h-8 w-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
            </div>

            @if ($subscription->current_period_end)
                <p class="text-sm text-gray-600">
                    Próxima cobrança em
                    <span class="font-semibold">{{ $subscription->current_period_end->format('d/m/Y') }}</span>
                </p>
            @endif

            <a href="{{ route('subscription.portal') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-white border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm">
                Gerenciar assinatura (trocar cartão, cancelar, faturas)
            </a>
        </div>

    {{-- ── Assinatura PIX ATIVA ─────────────────────────────────── --}}
    @elseif ($subscription?->gateway === 'mercadopago' && $subscription->status === 'active')
        <div class="rounded-2xl border-2 border-green-400 bg-green-50 p-6 space-y-3">
            <p class="text-lg font-bold text-green-700 flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                Ativa — PIX
            </p>
            @if ($subscription->current_period_end)
                <p class="text-sm text-gray-600">
                    Válida até <span class="font-semibold">{{ $subscription->current_period_end->format('d/m/Y') }}</span>.
                </p>
            @endif
        </div>

        {{-- Opções para renovar ou trocar --}}
        <div x-data="subscriptionPage(null)" class="space-y-3">
            <p class="text-sm font-semibold text-gray-700 text-center">Renovar ou trocar forma de pagamento</p>
            @include('app.settings._payment-options')
        </div>

    {{-- ── Sem assinatura / trial / expirada ───────────────────── --}}
    @else
        <div class="rounded-2xl border-2 border-primary-500 bg-white shadow-sm p-6 text-center">
            <p class="text-4xl font-bold text-gray-900">R$ 57,90</p>
            <p class="text-gray-400 text-sm mt-1">por mês</p>

            <ul class="mt-5 space-y-2 text-sm text-left text-gray-600">
                @foreach ([
                    'Agenda com múltiplos profissionais',
                    'Comandas e PDV completo',
                    'Controle de clientes e comissões',
                    'Relatórios financeiros',
                    'Suporte ilimitado',
                ] as $feature)
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div x-data="subscriptionPage({{ json_encode($pixData) }})" class="space-y-3">
            <p class="text-sm font-semibold text-gray-700 text-center">Como deseja pagar?</p>
            @include('app.settings._payment-options')
        </div>

        <p class="text-center text-xs text-gray-400">Cancele a qualquer momento. Sem fidelidade.</p>
    @endif

</div>

<script>
function subscriptionPage(existing) {
    return {
        // Controla qual método está selecionado: null | 'pix' | 'card'
        method: existing ? 'pix' : null,

        // Estado do PIX
        loading:      false,
        qrCode:       existing ? existing.qr_code        : null,
        qrCodeBase64: existing ? existing.qr_code_base64 : null,
        paymentId:    existing ? existing.payment_id     : null,
        paid:         false,
        copied:       false,
        pollTimer:    null,

        init() {
            if (this.qrCode) this.startPolling();
        },

        selectCard() {
            // Limpa QR code ao trocar para cartão
            this.qrCode       = null;
            this.qrCodeBase64 = null;
            this.paymentId    = null;
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.method = 'card';
        },

        generate() {
            const self = this;
            self.loading = true;
            self.method  = 'pix';

            fetch('{{ route('subscription.pix') }}', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept':       'application/json',
                },
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.error) { alert(data.error); self.method = null; return; }
                self.qrCode       = data.qr_code;
                self.qrCodeBase64 = data.qr_code_base64;
                self.paymentId    = data.payment_id;
                self.startPolling();
            })
            .catch(function(err) { alert('Erro ao gerar PIX: ' + err.message); self.method = null; })
            .finally(function()  { self.loading = false; });
        },

        startPolling() {
            const self = this;
            self.pollTimer = setInterval(function() {
                fetch('{{ route('subscription.pix.status') }}', {
                    headers: { 'Accept': 'application/json' },
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.status === 'active') {
                        self.paid = true;
                        clearInterval(self.pollTimer);
                    }
                });
            }, 5000);
        },

        copy() {
            const self = this;
            navigator.clipboard.writeText(self.qrCode).then(function() {
                self.copied = true;
                setTimeout(function() { self.copied = false; }, 2000);
            });
        }
    };
}
</script>
@endsection
