@extends('layouts.app')
@section('title', 'Processando pagamento…')

@section('content')
<div class="max-w-sm mx-auto text-center py-16 space-y-4" x-data="pendingPage()">

    <div x-show="!activated">
        <div class="text-5xl mb-4">⏳</div>
        <h1 class="text-xl font-bold text-gray-900">Processando seu pagamento</h1>
        <p class="text-gray-500 text-sm mt-2">
            Aguardando confirmação do Stripe. Sua conta será ativada automaticamente em instantes.
        </p>
        <div class="mt-4 flex items-center justify-center gap-2 text-sm text-amber-700">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Verificando…
        </div>
    </div>

    <div x-show="activated" x-cloak class="space-y-4">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto">
            <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Assinatura ativada!</h1>
        <p class="text-gray-500 text-sm">Redirecionando para o dashboard…</p>
    </div>

</div>

<script>
function pendingPage() {
    return {
        activated: false,
        timer: null,

        init() {
            const self = this;
            self.timer = setInterval(function() {
                fetch('{{ route('subscription.pix.status') }}', {
                    headers: { 'Accept': 'application/json' },
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.status === 'active') {
                        self.activated = true;
                        clearInterval(self.timer);
                        setTimeout(function() {
                            window.location.href = '{{ route('dashboard') }}';
                        }, 1500);
                    }
                });
            }, 3000);
        }
    };
}
</script>
@endsection
