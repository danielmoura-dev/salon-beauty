{{-- Modal de Comanda — incluir nas páginas que precisam abrir uma comanda --}}
<div
    x-data="orderModal()"
    @open-order-modal.window="open($event.detail.orderId)"
    x-show="show"
    x-cloak
    class="fixed inset-0 flex items-end sm:items-center justify-center p-4"
    style="z-index:100; display:none">

    <div class="absolute inset-0 bg-black/40" @click="close()"></div>

    <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-xl overflow-hidden flex flex-col max-h-[92vh] min-h-[280px]"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         @click.stop>

        {{-- Loading --}}
        <div x-show="loading" class="flex-1 flex flex-col items-center justify-center gap-3 py-12 text-gray-400">
            <svg class="animate-spin h-8 w-8 text-primary-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <p class="text-sm font-medium">Carregando comanda…</p>
        </div>

        {{-- Conteúdo --}}
        <div x-show="!loading && order" class="flex flex-col overflow-hidden flex-1">

            {{-- ===== VIEW: DETALHE ===== --}}
            <div x-show="view === 'detail'" class="flex flex-col overflow-hidden flex-1">

                {{-- Header --}}
                <div class="flex items-start justify-between gap-3 px-5 pt-8 pb-4 border-b border-gray-100">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 text-lg truncate" x-text="order?.client?.name"></p>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Aberta em ' + formatDate(order?.created_at)"></p>
                        <p x-show="order?.client?.balance > 0"
                           class="text-xs font-medium text-green-600 mt-0.5"
                           x-text="'Crédito: ' + fmt(order?.client?.balance)"></p>
                        <p x-show="order?.client?.balance < 0"
                           class="text-xs font-medium text-red-600 mt-0.5"
                           x-text="'Débito: ' + fmt(Math.abs(order?.client?.balance))"></p>
                    </div>
                    <div class="flex items-start gap-2 shrink-0">
                        <span class="text-xs font-semibold px-3 py-1 rounded-full mt-0.5"
                              :class="order?.status === 'open' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'"
                              x-text="order?.status === 'open' ? 'Aberta' : 'Fechada'"></span>
                        <button @click="close()" class="text-gray-400 hover:text-gray-600 mt-0.5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Corpo com scroll --}}
                <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

                    {{-- Itens --}}
                    <div class="rounded-2xl border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                            <p class="font-semibold text-gray-800 text-sm">Itens</p>
                            <button x-show="order?.status === 'open'"
                                    @click="openAddItem()"
                                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700">
                                + Adicionar
                            </button>
                        </div>

                        <template x-for="item in order?.items" :key="item.id">
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 text-sm" x-text="item.description"></p>
                                    <p class="text-xs text-gray-400"
                                       x-text="item.qty + 'x ' + fmt(item.unit_price) + (item.professional ? ' · ' + item.professional.name : '')"></p>
                                </div>
                                <p class="font-semibold text-gray-900 text-sm shrink-0"
                                   x-text="fmt(item.qty * item.unit_price)"></p>
                                <button x-show="order?.status === 'open'"
                                        @click="removeItem(item.id)"
                                        class="rounded-lg p-1.5 bg-red-50 text-red-500 hover:bg-red-100 shrink-0">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <div x-show="!order?.items?.length" class="px-4 py-6 text-center text-sm text-gray-400">
                            Nenhum item adicionado.
                        </div>

                        {{-- Total --}}
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-100">
                            <span class="font-semibold text-gray-700 text-sm">Total</span>
                            <span class="text-lg font-bold text-gray-900" x-text="fmt(order?.total)"></span>
                        </div>
                    </div>

                    {{-- Pagamentos --}}
                    <div class="rounded-2xl border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                            <p class="font-semibold text-gray-800 text-sm">Pagamentos</p>
                            <button x-show="order?.status === 'open'"
                                    @click="openAddPayment()"
                                    class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">
                                + Receber
                            </button>
                        </div>

                        <template x-for="payment in order?.payments" :key="payment.id">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 last:border-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-700"
                                       x-text="methodLabel(payment.method) + (payment.installments ? ' ' + payment.installments + 'x' : '')"></p>
                                </div>
                                <p class="text-sm font-semibold text-green-700" x-text="'+ ' + fmt(payment.amount)"></p>
                            </div>
                        </template>

                        <div x-show="!order?.payments?.length" class="px-4 py-6 text-center text-sm text-gray-400">
                            Nenhum pagamento registrado.
                        </div>

                        {{-- Saldo --}}
                        <template x-if="order?.total > 0">
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-100">
                                <span class="text-sm font-medium text-gray-600" x-text="balanceLabel()"></span>
                                <span class="font-bold"
                                      :class="balance() < 0 ? 'text-red-500' : 'text-green-600'"
                                      x-text="balance() === 0 && totalPaid() > 0 ? fmt(totalPaid()) : fmt(Math.abs(balance()))"></span>
                            </div>
                        </template>
                    </div>

                </div>

                {{-- Footer: ações --}}
                <div class="px-5 py-4 border-t border-gray-100 space-y-2">

                    {{-- Comanda aberta: 3 ações --}}
                    <template x-if="order?.status === 'open'">
                        <div class="space-y-2">
                            {{-- Fechar — sempre clicável; se não pago abre pagamento --}}
                            <button @click="handleCloseClick()" :disabled="saving"
                                    class="w-full rounded-2xl py-3.5 text-sm font-bold transition-colors bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-60">
                                <span x-show="!saving">Fechar Comanda</span>
                                <span x-show="saving">Aguarde…</span>
                            </button>
                            <div class="flex gap-2">
                                <button @click="close()" :disabled="saving"
                                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-60">
                                    Manter Aberta
                                </button>
                                <button @click="cancelOrder()" :disabled="saving"
                                        class="flex-1 rounded-xl border border-red-200 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-60">
                                    Cancelar Comanda
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Comanda fechada --}}
                    <template x-if="order?.status === 'closed'">
                        <button @click="reopenOrder()" :disabled="saving"
                                class="w-full rounded-2xl border border-gray-300 py-2.5 text-gray-600 font-medium text-sm hover:bg-gray-50 disabled:opacity-60 transition-colors">
                            Reabrir Comanda
                        </button>
                    </template>

                    {{-- Comanda cancelada --}}
                    <template x-if="order?.status === 'cancelled'">
                        <p class="text-center text-sm text-red-500 font-medium py-1">Comanda cancelada</p>
                    </template>

                </div>
            </div>

            {{-- ===== VIEW: ADICIONAR ITEM ===== --}}
            <div x-show="view === 'addItem'" class="flex flex-col overflow-hidden flex-1">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <button @click="view = 'detail'" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <h2 class="font-semibold text-gray-900">Adicionar Item</h2>
                </div>

                <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">
                    {{-- Tipo --}}
                    <div class="flex gap-2">
                        <template x-for="[val, label] in [['service','Serviço'],['product','Produto'],['other','Outro']]" :key="val">
                            <button type="button"
                                    @click="itemForm.type = val"
                                    :class="itemForm.type === val
                                        ? 'border-primary-500 bg-primary-50 text-primary-700'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                    class="flex-1 rounded-xl border-2 py-2 text-sm font-medium transition-colors"
                                    x-text="label"></button>
                        </template>
                    </div>

                    {{-- Quick fill serviço --}}
                    <div x-show="itemForm.type === 'service'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preencher a partir de:</label>
                        <select @change="fillFromService($event.target.value); $event.target.value = ''"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Selecione um serviço…</option>
                            <template x-for="svc in formData.services" :key="svc.id">
                                <option :value="svc.id" x-text="svc.name + ' — R$ ' + Number(svc.price).toFixed(2).replace('.',',')"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Quick fill produto --}}
                    <div x-show="itemForm.type === 'product'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preencher a partir de:</label>
                        <select @change="fillFromProduct($event.target.value); $event.target.value = ''"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Selecione um produto…</option>
                            <template x-for="prod in formData.products" :key="prod.id">
                                <option :value="prod.id" x-text="prod.name + ' — R$ ' + Number(prod.price).toFixed(2).replace('.',',')"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Descrição --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                        <input type="text" x-model="itemForm.description" required
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qtd</label>
                            <input type="number" x-model.number="itemForm.qty" min="1"
                                class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor unit. (R$)</label>
                            <input type="number" x-model.number="itemForm.unit_price" step="0.01" min="0"
                                class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>

                    {{-- Profissional --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Profissional</label>
                        <select x-model="itemForm.professional_id"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Sem profissional</option>
                            <template x-for="prof in formData.professionals" :key="prof.id">
                                <option :value="prof.id" x-text="prof.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Comissão --}}
                    <label class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Gera comissão</span>
                        <input type="checkbox" x-model="itemForm.has_commission"
                               class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                    </label>

                    <div x-show="itemForm.has_commission">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comissão (%)</label>
                        <input type="number" x-model.number="itemForm.commission_pct" min="0" max="100" step="0.5"
                            class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    {{-- Subtotal preview --}}
                    <div class="rounded-xl bg-gray-50 px-4 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Subtotal</span>
                        <span class="font-bold text-gray-900"
                              x-text="fmt(itemForm.qty * itemForm.unit_price)"></span>
                    </div>

                    <p x-show="itemError" x-text="itemError" class="text-sm text-red-500 text-center"></p>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex gap-2">
                    <button type="button" @click="view = 'detail'"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="addItem()" :disabled="saving"
                        class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
                        <span x-show="!saving">Adicionar</span>
                        <span x-show="saving">Salvando…</span>
                    </button>
                </div>
            </div>

            {{-- ===== VIEW: AVISO DE DÍVIDA ===== --}}
            <div x-show="view === 'debtWarning'" class="flex flex-col flex-1">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <button @click="view = 'detail'" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <h2 class="font-semibold text-gray-900">Dívida na casa</h2>
                </div>
                <div class="flex-1 flex flex-col items-center justify-center px-6 py-8 text-center space-y-4">
                    <div class="h-14 w-14 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="h-7 w-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Cliente com dívida</p>
                        <p class="text-sm text-gray-500 mt-1">
                            <span x-text="order?.client?.name"></span> deve
                            <span class="font-semibold text-red-600" x-text="fmt(clientDebt)"></span>.
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Deseja cobrar a dívida junto com esta comanda?</p>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-100 flex gap-2">
                    <button @click="startPaymentForm(false)"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Não, ignorar
                    </button>
                    <button @click="chargeDebtAndPay()"  :disabled="saving"
                        class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
                        <span x-show="!saving">Sim, cobrar agora</span>
                        <span x-show="saving">Adicionando…</span>
                    </button>
                </div>
            </div>

            {{-- ===== VIEW: AVISO DE CRÉDITO ===== --}}
            <div x-show="view === 'creditWarning'" class="flex flex-col flex-1">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <button @click="view = 'detail'" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <h2 class="font-semibold text-gray-900">Crédito na casa</h2>
                </div>
                <div class="flex-1 flex flex-col items-center justify-center px-6 py-8 text-center space-y-4">
                    <div class="h-14 w-14 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Cliente tem crédito</p>
                        <p class="text-sm text-gray-500 mt-1">
                            <span x-text="order?.client?.name"></span> tem
                            <span class="font-semibold text-green-600" x-text="fmt(clientCredit)"></span> de crédito.
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Deseja usar o crédito no pagamento?</p>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-100 flex gap-2">
                    <button @click="startPaymentForm(false, false)"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Não, ignorar
                    </button>
                    <button @click="startPaymentForm(false, true)"
                        class="flex-1 rounded-xl bg-green-600 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                        Sim, usar crédito
                    </button>
                </div>
            </div>

            {{-- ===== VIEW: RECEBER PAGAMENTO (multi-método) ===== --}}
            <div x-show="view === 'addPayment'" class="flex flex-col overflow-hidden flex-1">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <button @click="view = 'detail'" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <h2 class="font-semibold text-gray-900">Receber Pagamento</h2>
                </div>

                <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

                    {{-- Resumo em tempo real --}}
                    <div class="rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 space-y-1.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Valor da comanda</span>
                            <span class="font-medium text-gray-900" x-text="fmt(order?.total)"></span>
                        </div>
                        <div x-show="debtBeingCharged > 0" class="flex items-center justify-between">
                            <span class="text-red-500">Cobrança de dívida</span>
                            <span class="font-medium text-red-500" x-text="fmt(debtBeingCharged)"></span>
                        </div>
                        <template x-if="totalFeeEntering() > 0">
                            <div class="flex items-center justify-between">
                                <span class="text-orange-500 text-xs">Taxa da maquininha</span>
                                <span class="font-medium text-orange-500 text-xs" x-text="'+ ' + fmt(totalFeeEntering())"></span>
                            </div>
                        </template>
                        <div class="flex items-center justify-between border-t border-gray-200 pt-1.5">
                            <span class="text-gray-700 font-semibold">Total</span>
                            <span class="font-bold text-gray-900" x-text="fmt(sessionTotal())"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Valor inserido</span>
                            <span class="font-semibold text-blue-600" x-text="fmt(totalEntering())"></span>
                        </div>
                        {{-- Troco/Falta: só aparece quando o usuário digitou algo --}}
                        <div x-show="totalEntering() > 0"
                             class="flex items-center justify-between border-t border-gray-200 pt-1.5">
                            <span class="font-semibold"
                                  :class="sessionChange() < -0.01 ? 'text-red-500' : 'text-gray-700'"
                                  x-text="sessionChange() < -0.01 ? 'Falta' : 'Troco'"></span>
                            <span class="font-bold"
                                  :class="sessionChange() < -0.01 ? 'text-red-500' : 'text-gray-800'"
                                  x-text="fmt(Math.abs(sessionChange()))"></span>
                        </div>
                    </div>

                    {{-- Entradas de pagamento --}}
                    <template x-for="(entry, idx) in paymentEntries" :key="idx">
                        <div class="rounded-xl p-3 space-y-3"
                             :class="entry.locked ? 'border-2 border-green-400 bg-green-50' : 'border border-gray-200'">

                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wide"
                                   :class="entry.locked ? 'text-green-700' : 'text-gray-500'"
                                   x-text="entry.locked ? 'Crédito da casa' : (idx === 0 ? 'Forma de pagamento' : '2ª forma de pagamento')"></p>
                                <button x-show="!entry.locked && idx > 0"
                                        @click="paymentEntries.splice(idx, 1)"
                                        class="text-xs text-red-500 hover:text-red-700">Remover</button>
                            </div>

                            {{-- Forma de pagamento: travada --}}
                            <div x-show="entry.locked"
                                 class="rounded-lg border-2 border-green-400 bg-white py-2 px-3 text-center text-sm font-semibold text-green-700">
                                Crédito da casa (fixo)
                            </div>

                            {{-- Forma de pagamento: normal --}}
                            <div x-show="!entry.locked" class="grid grid-cols-3 gap-1.5">
                                <template x-for="[val, label] in Object.entries(paymentMethods)" :key="val">
                                    <button type="button"
                                            @click="selectMethod(entry, val)"
                                            :class="entry.method === val
                                                ? 'border-primary-500 bg-primary-50 text-primary-700'
                                                : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                            class="rounded-xl border-2 py-1.5 text-center text-xs font-medium transition-colors">
                                        <span x-text="label"></span>
                                        <template x-if="feeForMethod(val) > 0">
                                            <span class="block text-[10px] opacity-70" x-text="'+' + feeForMethod(val) + '%'"></span>
                                        </template>
                                    </button>
                                </template>
                            </div>

                            {{-- Valor: travado --}}
                            <div x-show="entry.locked" class="flex items-center justify-between">
                                <span class="text-xs text-green-600">Valor fixo</span>
                                <span class="font-bold text-green-700" x-text="fmt(entry.amount)"></span>
                            </div>

                            {{-- Parcelas: somente para cartão de crédito --}}
                            <div x-show="!entry.locked && entry.method === 'credit_card'">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Parcelas *</label>
                                <select x-model.number="entry.installments"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione…</option>
                                    <template x-for="n in [1,2,3,4,5,6,7,8,9,10,11,12]" :key="n">
                                        <option :value="n" x-text="n + 'x'"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Valor: editável --}}
                            <div x-show="!entry.locked">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Valor (R$)</label>
                                <input type="number" x-model.number="entry.amount" step="0.01" min="0"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </template>

                    {{-- Botão adicionar 2ª forma — só aparece se houver menos de 2 e nenhuma travada (crédito) --}}
                    <button x-show="paymentEntries.length < 2 && !paymentEntries.some(e => e.locked)"
                            @click="addSecondPaymentEntry()"
                            class="w-full rounded-xl border border-dashed border-gray-300 py-2 text-sm text-gray-500 hover:border-primary-400 hover:text-primary-600 transition-colors">
                        + Adicionar 2ª forma de pagamento
                    </button>

                    <p x-show="paymentError" x-text="paymentError" class="text-sm text-red-500 text-center"></p>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex gap-2">
                    <button type="button" @click="view = 'detail'"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="checkCreditBeforeSubmit()" :disabled="saving"
                        class="flex-1 rounded-xl bg-green-600 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60">
                        <span x-show="!saving">Confirmar</span>
                        <span x-show="saving">Salvando…</span>
                    </button>
                </div>
            </div>

            {{-- ===== VIEW: CONFIRMAÇÃO DE CRÉDITO ===== --}}
            <div x-show="view === 'creditConfirm'" class="flex flex-col flex-1">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <button @click="view = 'addPayment'" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <h2 class="font-semibold text-gray-900">Confirmar pagamento</h2>
                </div>
                <div class="flex-1 flex flex-col items-center justify-center px-6 py-8 text-center space-y-4">
                    <div class="h-14 w-14 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Vai sobrar crédito</p>
                        <p class="text-sm text-gray-500 mt-1">
                            O valor pago é maior que o total da comanda.
                            <span class="font-semibold text-green-600" x-text="fmt(creditToConfirm)"></span>
                            ficará de crédito para <span x-text="order?.client?.name"></span>.
                        </p>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-100 flex gap-2">
                    <button @click="view = 'addPayment'"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Voltar
                    </button>
                    <button @click="submitPayments()" :disabled="saving"
                        class="flex-1 rounded-xl bg-green-600 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60">
                        <span x-show="!saving">Confirmar</span>
                        <span x-show="saving">Salvando…</span>
                    </button>
                </div>
            </div>

            {{-- ===== VIEW: SHORTFALL (valor em falta) ===== --}}
            <div x-show="view === 'shortfall'" class="flex flex-col flex-1">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Pagamento incompleto</h2>
                </div>
                <div class="flex-1 flex flex-col items-center justify-center px-6 py-8 text-center space-y-4">
                    <div class="h-14 w-14 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Falta
                            <span class="text-amber-600" x-text="fmt(shortfallAmount)"></span>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">O pagamento não cobre o total da comanda.</p>
                        <p class="text-sm text-gray-500">Deseja lançar o valor restante como dívida?</p>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-100 flex gap-2">
                    <button @click="applyDiscount()" :disabled="saving"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                        Não, foi desconto
                    </button>
                    <button @click="applyDebt()" :disabled="saving"
                        class="flex-1 rounded-xl bg-amber-600 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60">
                        <span x-show="!saving">Sim, lançar como dívida</span>
                        <span x-show="saving">Salvando…</span>
                    </button>
                </div>
            </div>

        </div>{{-- fim conteúdo --}}
    </div>
</div>

<script>
function orderModal() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    return {
        show:            false,
        loading:         false,
        saving:          false,
        order:           null,
        view:            'detail',
        formDataLoaded:  false,
        itemError:       '',
        paymentError:    '',
        paymentEntries:    [],
        shortfallAmount:   0,
        creditToConfirm:   0,
        clientDebt:        0,
        clientCredit:      0,
        debtBeingCharged:  0,

        formData: { services: [], products: [], professionals: [], fees: { credit_card: 0, debit_card: 0 } },

        itemForm: {
            type: 'service', description: '', qty: 1, unit_price: 0,
            professional_id: '', commission_pct: 0, has_commission: true,
        },

        paymentMethods: {
            pix: 'Pix', credit_card: 'Cartão Crédito', debit_card: 'Cartão Débito',
            cash: 'Dinheiro', credit: 'Crédito', debt: 'Fiado',
        },

        async open(orderId) {
            this.show             = true;
            this.view             = 'detail';
            this.loading          = true;
            this.order            = null;
            this.paymentEntries   = [];
            this.debtBeingCharged = 0;
            await Promise.all([
                this.loadOrder(orderId),
                this.formDataLoaded ? Promise.resolve() : this.loadFormData(),
            ]);
            this.loading = false;
        },

        close() { this.show = false; this.order = null; },

        async loadOrder(id) {
            const res  = await fetch(`/orders/${id}/data`, { headers: { Accept: 'application/json' } });
            this.order = await res.json();
        },

        async loadFormData() {
            const res       = await fetch('/orders/form-data', { headers: { Accept: 'application/json' } });
            this.formData   = await res.json();
            this.formDataLoaded = true;
        },

        openAddItem() {
            this.itemError = '';
            this.itemForm  = { type: 'service', description: '', qty: 1, unit_price: 0, professional_id: '', commission_pct: 0, has_commission: true };
            this.view      = 'addItem';
        },

        handleCloseClick() {
            if (this.isPaid()) {
                this.closeOrder();
            } else {
                this.openAddPayment();
            }
        },

        async openAddPayment() {
            this.paymentError   = '';
            this.paymentEntries = [];

            // Se já existem pagamentos nesta comanda, estamos editando —
            // estorna os pagamentos anteriores e vai direto ao formulário,
            // sem mostrar avisos de crédito/débito (que viriam desse mesmo pagamento).
            if ((this.order?.payments ?? []).length > 0) {
                this.saving = true;
                try {
                    const res = await fetch(`/orders/${this.order.id}/payments/clear`, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    });
                    if (res.ok) {
                        this.order = await res.json();
                    } else {
                        alert('Erro ao limpar pagamentos.');
                        return;
                    }
                } finally { this.saving = false; }
                this.startPaymentForm(false, false);
                return;
            }

            const balance = Number(this.order?.client?.balance ?? 0);
            if (balance < 0) {
                this.clientDebt = parseFloat(Math.abs(balance).toFixed(2));
                this.view = 'debtWarning';
            } else if (balance > 0) {
                this.clientCredit = parseFloat(balance.toFixed(2));
                this.view = 'creditWarning';
            } else {
                this.startPaymentForm(false, false);
            }
        },

        feeForMethod(method) {
            return parseFloat(this.formData.fees?.[method] ?? 0);
        },

        amountWithFee(base, method) {
            const fee = this.feeForMethod(method);
            if (fee <= 0) return base;
            return parseFloat((base * (1 + fee / 100)).toFixed(2));
        },

        feeAmountFor(base, method) {
            return parseFloat((this.amountWithFee(base, method) - base).toFixed(2));
        },

        selectMethod(entry, method) {
            entry.method    = method;
            entry.amount    = this.amountWithFee(entry.baseAmount, method);
            entry.feePct    = this.feeForMethod(method);
            entry.feeAmount = this.feeAmountFor(entry.baseAmount, method);
        },

        startPaymentForm(includeDebt, useCredit) {
            this.paymentError     = '';
            this.paymentEntries   = [];
            this.debtBeingCharged = includeDebt ? this.clientDebt : 0;

            const alreadyPaid = this.totalPaid();
            const fullTotal   = Number(this.order.total) + (includeDebt ? this.clientDebt : 0);
            const totalDue    = parseFloat(Math.max(fullTotal - alreadyPaid, 0).toFixed(2));

            // Método do último pagamento como sugestão
            const payments   = this.order?.payments ?? [];
            const lastMethod = payments.length ? payments[payments.length - 1].method : 'pix';

            if (useCredit && this.clientCredit > 0) {
                const creditAmt = parseFloat(Math.min(this.clientCredit, totalDue).toFixed(2));
                const secondBase = parseFloat(Math.max(totalDue - creditAmt, 0).toFixed(2));
                this.paymentEntries = [
                    { method: 'credit',   amount: creditAmt,                            baseAmount: creditAmt,  feePct: 0, feeAmount: 0, installments: null, notes: '', locked: true },
                    { method: lastMethod, amount: this.amountWithFee(secondBase, lastMethod), baseAmount: secondBase, feePct: this.feeForMethod(lastMethod), feeAmount: this.feeAmountFor(secondBase, lastMethod), installments: null, notes: '', locked: false },
                ];
            } else {
                this.paymentEntries = [{
                    method:       lastMethod,
                    amount:       this.amountWithFee(totalDue, lastMethod),
                    baseAmount:   totalDue,
                    feePct:       this.feeForMethod(lastMethod),
                    feeAmount:    this.feeAmountFor(totalDue, lastMethod),
                    installments: null,
                    notes:        '',
                    locked:       false,
                }];
            }
            this.view = 'addPayment';
        },

        chargeDebtAndPay() {
            this.startPaymentForm(true, false);
        },

        async addItem() {
            if (!this.itemForm.description) { this.itemError = 'Informe a descrição.'; return; }
            this.saving = true; this.itemError = '';
            try {
                const res = await fetch(`/orders/${this.order.id}/items`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify(this.itemForm),
                });
                if (res.ok) { this.order = await res.json(); this.view = 'detail'; }
                else { this.itemError = 'Erro ao adicionar item.'; }
            } finally { this.saving = false; }
        },

        async removeItem(itemId) {
            if (!confirm('Remover este item da comanda?')) return;
            const res = await fetch(`/orders/${this.order.id}/items/${itemId}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (res.ok) this.order = await res.json();
        },

        addSecondPaymentEntry() {
            const remaining = parseFloat(Math.max(
                this.sessionTotal() - this.totalPaid() - this.totalBaseEntering(), 0
            ).toFixed(2));
            this.paymentEntries.push({
                method: 'cash', amount: remaining, baseAmount: remaining,
                feePct: 0, feeAmount: 0, installments: null, notes: '', locked: false,
            });
        },

        checkCreditBeforeSubmit() {
            const entries = this.paymentEntries.filter(e => Number(e.amount) > 0);
            if (!entries.length) { this.paymentError = 'Informe ao menos um valor.'; return; }

            const missingInstallments = entries.some(e => e.method === 'credit_card' && !e.installments);
            if (missingInstallments) { this.paymentError = 'Informe o número de parcelas para cartão de crédito.'; return; }

            const credit = parseFloat(this.sessionChange().toFixed(2));
            if (credit > 0.01) {
                this.creditToConfirm = credit;
                this.view = 'creditConfirm';
                return;
            }
            this.submitPayments();
        },

        async submitPayments() {
            const entries = this.paymentEntries.filter(e => Number(e.amount) > 0);
            if (!entries.length) { this.paymentError = 'Informe ao menos um valor.'; return; }
            this.saving = true; this.paymentError = '';
            try {
                for (const entry of entries) {
                    const payload = {
                        method:        entry.method,
                        installments:  entry.method === 'credit_card' ? (entry.installments ?? null) : null,
                        amount:        entry.amount,
                        fee_pct:       entry.feePct    ?? 0,
                        fee_amount:    entry.feeAmount ?? 0,
                        notes:         entry.notes     ?? '',
                    };
                    const res = await fetch(`/orders/${this.order.id}/payments`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                        body: JSON.stringify(payload),
                    });
                    if (!res.ok) { this.paymentError = 'Erro ao registrar pagamento.'; return; }
                    this.order = await res.json();
                }
                // Verifica shortfall
                const remaining = Number(this.order.total) - this.totalPaid();
                if (remaining > 0.01) {
                    this.shortfallAmount = parseFloat(remaining.toFixed(2));
                    this.paymentEntries  = [];
                    this.view = 'shortfall';
                } else {
                    this.paymentEntries = [];
                    this.view = 'detail';
                }
            } finally { this.saving = false; }
        },

        applyDiscount() {
            // Pagamento parcial aceito como desconto — volta para detalhe
            this.view = 'detail';
        },

        async applyDebt() {
            this.saving = true;
            try {
                const res = await fetch(`/orders/${this.order.id}/payments`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ method: 'debt', amount: this.shortfallAmount, notes: 'Lançado como dívida' }),
                });
                if (res.ok) { this.order = await res.json(); this.view = 'detail'; }
                else { alert('Erro ao lançar dívida.'); }
            } finally { this.saving = false; }
        },

        async closeOrder() {
            this.saving = true;
            const res = await fetch(`/orders/${this.order.id}/close`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (res.ok) this.order = await res.json();
            this.saving = false;
        },

        async reopenOrder() {
            this.saving = true;
            const res = await fetch(`/orders/${this.order.id}/reopen`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (res.ok) this.order = await res.json();
            this.saving = false;
        },

        async cancelOrder() {
            if (!confirm('Cancelar esta comanda?')) return;
            this.saving = true;
            const res = await fetch(`/orders/${this.order.id}/cancel`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (res.ok) this.order = await res.json();
            this.saving = false;
        },

        isPaid() {
            return this.totalPaid() >= Number(this.order?.total ?? 0) && Number(this.order?.total ?? 0) > 0;
        },

        fillFromService(id) {
            const s = this.formData.services.find(s => s.id === id);
            if (s) { this.itemForm.description = s.name; this.itemForm.unit_price = parseFloat(s.price); this.itemForm.commission_pct = parseFloat(s.commission_pct ?? 0); }
        },

        fillFromProduct(id) {
            const p = this.formData.products.find(p => p.id === id);
            if (p) { this.itemForm.description = p.name; this.itemForm.unit_price = parseFloat(p.price); this.itemForm.commission_pct = parseFloat(p.commission_pct ?? 0); }
        },

        totalPaid() {
            // Usa valor efetivo (sem taxa) igual ao backend
            return (this.order?.payments ?? []).reduce((s, p) => s + Number(p.amount) - Number(p.fee_amount ?? 0), 0);
        },

        totalEntering() {
            // Valor bruto que o cliente vai pagar (inclui taxa)
            return this.paymentEntries.reduce((s, e) => s + Number(e.amount ?? 0), 0);
        },

        totalBaseEntering() {
            // Valor efetivo sem taxa — o que vai quitar a comanda
            return this.paymentEntries.reduce((s, e) => s + Number(e.baseAmount ?? e.amount ?? 0), 0);
        },

        totalFeeEntering() {
            return parseFloat((this.totalEntering() - this.totalBaseEntering()).toFixed(2));
        },

        // Total exibido = comanda + dívida sendo cobrada
        sessionTotal() {
            return Number(this.order?.total ?? 0) + this.debtBeingCharged;
        },

        // Positivo = troco, negativo = falta (baseado no valor efetivo, sem taxa)
        sessionChange() {
            return this.totalBaseEntering() - (this.sessionTotal() - this.totalPaid());
        },

        remainingAfterEntry() {
            return Number(this.order?.total ?? 0) - this.totalPaid() - this.totalBaseEntering();
        },

        balance() {
            return this.totalPaid() - Number(this.order?.total ?? 0);
        },

        balanceLabel() {
            const b = this.balance();
            if (b > 0)  return 'Troco / Crédito';
            if (b < 0)  return 'Faltam';
            return 'Pago';
        },

        fmt(val) {
            return 'R$ ' + Number(val ?? 0).toFixed(2).replace('.', ',');
        },

        methodLabel(method) {
            return this.paymentMethods[method] ?? method;
        },

        formatDate(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            return d.toLocaleDateString('pt-BR') + ' às ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
