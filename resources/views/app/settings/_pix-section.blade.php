{{-- Botão para gerar QR --}}
<button
    @click="generate()"
    :disabled="loading || qrCode"
    x-show="!qrCode"
    class="w-full flex items-center justify-between px-5 py-4 hover:border-green-400 hover:bg-green-50 transition-colors group disabled:opacity-60 disabled:cursor-not-allowed"
>
    <div class="text-left">
        <p class="font-semibold text-gray-900 group-hover:text-green-700 flex items-center gap-2">
            <svg class="h-5 w-5 text-green-500" viewBox="0 0 512 512" fill="currentColor"><path d="M112.57,391.19c20.056,0,38.928-7.808,53.12-22l76.693-76.692c5.696-5.664,15.36-5.664,21.024,0.032l76.96,76.96c14.192,14.16,33.056,21.968,53.12,21.968h15.04l-97.2,97.2c-29.344,29.376-76.992,29.376-106.368,0L108,391.19H112.57z"/><path d="M399.488,120.909c-20.064,0-38.928,7.808-53.12,21.968l-76.96,76.992c-5.792,5.792-15.232,5.792-21.024,0l-76.693-76.66c-14.192-14.192-33.064-22-53.12-22H112L208.97,23.981c29.376-29.376,77.024-29.376,106.368,0l97.2,97.2L399.488,120.909z"/><path d="M23.98,208.04l55.04-55.04h37.55c13.664,0,26.848,5.504,36.48,15.104l76.693,76.662c15.392,15.392,40.256,15.424,55.68,0.032l76.928-76.992c9.664-9.632,22.848-15.104,36.48-15.104h42.016l55.072,55.04c29.376,29.344,29.376,76.992,0,106.368l-55.072,55.008H402.31c-13.632,0-26.816-5.472-36.48-15.104l-76.928-76.96c-7.68-7.68-17.792-11.52-27.84-11.52s-20.16,3.84-27.84,11.488l-76.693,76.693c-9.632,9.632-22.816,15.104-36.48,15.104H83.02L23.98,314.408C-5.332,285.032-5.332,237.384,23.98,208.04z"/></svg>
            Pix
        </p>
        <p class="text-xs text-gray-400">Pague agora — renovação manual todo mês com novo QR</p>
    </div>
    <span x-show="!loading" class="text-gray-300 group-hover:text-green-500">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
    </span>
    <svg x-show="loading" class="h-5 w-5 animate-spin text-green-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
</button>

{{-- QR code --}}
<div x-show="qrCode" x-cloak class="p-5 space-y-4">

    {{-- Aguardando pagamento --}}
    <div x-show="!paid" class="space-y-4">
        <div class="flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
            <svg class="h-4 w-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            Aguardando pagamento…
        </div>

        <div class="flex justify-center">
            <img :src="'data:image/png;base64,' + qrCodeBase64" alt="QR Code PIX"
                 class="w-52 h-52 rounded-xl border border-gray-200 p-2">
        </div>

        <div>
            <p class="text-xs text-gray-500 mb-1 font-medium">Copia e cola:</p>
            <div class="flex gap-2">
                <input
                    type="text"
                    :value="qrCode"
                    readonly
                    class="flex-1 text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-600 font-mono truncate"
                >
                <button
                    @click="copy()"
                    class="shrink-0 px-3 py-2 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-100 transition"
                >
                    <span x-show="!copied">Copiar</span>
                    <span x-show="copied" class="text-green-600">Copiado!</span>
                </button>
            </div>
        </div>

        <p class="text-xs text-center text-gray-400">O QR code expira em 24 horas</p>
    </div>

    {{-- Pago! --}}
    <div x-show="paid" class="text-center space-y-3 py-4">
        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto">
            <svg class="h-7 w-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <p class="font-semibold text-gray-900">Pagamento confirmado!</p>
        <p class="text-sm text-gray-500">Sua assinatura está ativa por 1 mês.</p>
        <a href="{{ route('dashboard') }}" class="inline-block rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
            Ir para o dashboard
        </a>
    </div>
</div>

<script>
function pixPayment(existing) {
    return {
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

        generate() {
            const self = this;
            self.loading = true;

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
                self.qrCode       = data.qr_code;
                self.qrCodeBase64 = data.qr_code_base64;
                self.paymentId    = data.payment_id;
                self.startPolling();
            })
            .catch(function(err) { alert('Erro ao gerar PIX: ' + err.message); })
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
