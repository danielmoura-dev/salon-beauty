<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $affiliate->name }} — Admin Salon Beauty</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #0a0a0f; }
        .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.08); }
        .glow-violet { box-shadow: 0 0 24px rgba(139, 92, 246, 0.25); }
        .badge-active  { background: rgba(16,185,129,.15); color: #34d399; border: 1px solid rgba(16,185,129,.3); }
        .badge-trial   { background: rgba(245,158,11,.15); color: #fbbf24; border: 1px solid rgba(245,158,11,.3); }
        .badge-expired { background: rgba(239,68,68,.15);  color: #f87171; border: 1px solid rgba(239,68,68,.3); }
        .row-hover:hover { background: rgba(255,255,255,0.03); }
    </style>
</head>
<body class="min-h-screen font-sans antialiased text-gray-100" x-data="detailPage()">

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="w-56 shrink-0 flex flex-col border-r border-white/[0.06]" style="background:#0f0f1a">
        <div class="px-5 py-6 border-b border-white/[0.06]">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-700 flex items-center justify-center text-white font-bold text-sm shadow-lg glow-violet">S</div>
                <div>
                    <p class="text-white font-semibold text-sm leading-none">SalonBeauty</p>
                    <p class="text-violet-400 text-xs mt-0.5">Admin Console</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-0.5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 text-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.affiliates.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-violet-600/20 text-violet-300 text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Afiliados
            </a>
        </nav>
        <div class="px-4 py-4 border-t border-white/[0.06]">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-xs text-gray-500 hover:text-gray-300 transition text-left px-1">→ Sair</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="flex items-center justify-between px-8 py-4 border-b border-white/[0.06]" style="background:#0f0f1a">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.affiliates.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 class="text-white font-semibold text-lg">{{ $affiliate->name }}</h1>
                    <p class="text-gray-500 text-xs flex items-center gap-2">
                        <span class="font-mono text-violet-400">{{ $affiliate->code }}</span>
                        <span>·</span>
                        <span>{{ $affiliate->email }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if ($pendingTotal > 0)
                <button @click="markPaid()" :disabled="paying" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <span x-show="!paying">Marcar como pago — R$ {{ number_format($pendingTotal, 2, ',', '.') }}</span>
                    <span x-show="paying" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Salvando…
                    </span>
                </button>
                @endif
                <button @click="showEdit = true" class="flex items-center gap-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar
                </button>
            </div>
        </header>

        <main class="flex-1 px-8 py-7 space-y-7 overflow-auto">

            {{-- Info cards --}}
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="glass rounded-xl p-5">
                    <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-2">Comissão por pagamento</p>
                    <p class="text-white font-bold text-2xl">{{ number_format($affiliate->commission_pct, 1) }}%</p>
                    <p class="text-gray-600 text-xs mt-1">sobre valor cobrado</p>
                </div>
                <div class="glass rounded-xl p-5">
                    <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-2">Desconto do cupom</p>
                    <p class="text-violet-400 font-bold text-2xl">{{ number_format($affiliate->discount_pct, 1) }}%</p>
                    <p class="text-gray-600 text-xs mt-1">nos 3 primeiros meses</p>
                </div>
                <div class="glass rounded-xl p-5">
                    <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-2">Contas indicadas</p>
                    <p class="text-white font-bold text-2xl">{{ $tenants->count() }}</p>
                    <p class="text-gray-600 text-xs mt-1">{{ $tenants->where('plan_status', 'active')->count() }} pagantes</p>
                </div>
                <div class="glass rounded-xl p-5">
                    <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-2">Comissão pendente</p>
                    <p class="text-amber-400 font-bold text-xl">R$ {{ number_format($pendingTotal, 2, ',', '.') }}</p>
                    <p class="text-gray-600 text-xs mt-1">a pagar</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-7">

                {{-- Contas indicadas --}}
                <div class="glass rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-white/[0.06]">
                        <h2 class="text-white font-semibold text-sm">Contas Indicadas</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/[0.06]">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Negócio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Cadastro</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Desconto restante</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.04]">
                                @forelse ($tenants as $tenant)
                                <tr class="row-hover transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="text-white text-sm font-medium">{{ $tenant->name }}</p>
                                        <p class="text-gray-600 text-xs">{{ $tenant->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400 text-xs font-mono">{{ $tenant->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $cls = match($tenant->plan_status) { 'active' => 'badge-active', 'trial' => 'badge-trial', default => 'badge-expired' };
                                            $lbl = match($tenant->plan_status) { 'active' => 'Ativo', 'trial' => 'Trial', default => ucfirst($tenant->plan_status) };
                                        @endphp
                                        <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full {{ $cls }}">{{ $lbl }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-mono">
                                        @if ($tenant->affiliate_discount_months_remaining > 0)
                                            <span class="text-violet-400">{{ $tenant->affiliate_discount_months_remaining }} {{ $tenant->affiliate_discount_months_remaining === 1 ? 'mês' : 'meses' }}</span>
                                        @else
                                            <span class="text-gray-700">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-gray-600 text-xs">Nenhuma conta indicada ainda.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Comissões por período --}}
                <div class="glass rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-white/[0.06]">
                        <h2 class="text-white font-semibold text-sm">Comissões por Período</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/[0.06]">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Período</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Pagamentos</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Comissão</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Pago</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Pendente</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.04]" id="commissions-table">
                                @forelse ($commissionsByPeriod as $row)
                                <tr class="row-hover transition-colors" data-period="{{ $row->period }}">
                                    <td class="px-4 py-3 text-gray-300 font-mono text-xs">{{ $row->period }}</td>
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $row->payment_count }}</td>
                                    <td class="px-4 py-3 text-white font-mono text-xs">R$ {{ number_format($row->total, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-emerald-400 font-mono text-xs">R$ {{ number_format($row->paid, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 font-mono text-xs {{ $row->pending > 0 ? 'text-amber-400' : 'text-gray-600' }}">R$ {{ number_format($row->pending, 2, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-gray-600 text-xs">Nenhuma comissão registrada ainda.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

{{-- Modal: Editar Afiliado --}}
<div x-show="showEdit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.7)">
    <div class="glass rounded-2xl w-full max-w-md p-6 space-y-5" @click.outside="showEdit = false">
        <div class="flex items-center justify-between">
            <h3 class="text-white font-semibold text-base">Editar Afiliado</h3>
            <button @click="showEdit = false" class="text-gray-500 hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Nome</label>
                <input type="text" x-model="editName"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">E-mail</label>
                <input type="email" x-model="editEmail"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">WhatsApp</label>
                <input type="text" x-model="editWhatsapp" placeholder="(11) 99999-9999"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-violet-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Comissão (%)</label>
                    <input type="number" x-model="editCommissionPct" min="0.01" max="100" step="0.01"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Desconto (%)</label>
                    <input type="number" x-model="editDiscountPct" min="0.01" max="100" step="0.01"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-violet-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Observações</label>
                <textarea x-model="editNotes" rows="2"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-violet-500 resize-none"></textarea>
            </div>
            <p x-show="editError" x-text="editError" class="text-red-400 text-xs"></p>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="button" @click="showEdit = false" class="flex-1 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium py-2.5 rounded-lg transition-colors">Cancelar</button>
            <button type="button" @click="saveEdit()" :disabled="saving" class="flex-1 bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white text-sm font-medium py-2.5 rounded-lg transition-colors">
                <span x-show="!saving">Salvar</span>
                <span x-show="saving">Salvando…</span>
            </button>
        </div>
    </div>
</div>

<script>
function detailPage() {
    return {
        showEdit: false,
        saving: false,
        editError: '',
        paying: false,
        editName: '{{ addslashes($affiliate->name) }}',
        editEmail: '{{ $affiliate->email }}',
        editWhatsapp: '{{ $affiliate->whatsapp ?? '' }}',
        editCommissionPct: '{{ $affiliate->commission_pct }}',
        editDiscountPct: '{{ $affiliate->discount_pct }}',
        editNotes: '{{ addslashes($affiliate->notes ?? '') }}',

        saveEdit() {
            const self = this;
            self.saving = true;
            self.editError = '';
            fetch('/admin/affiliates/{{ $affiliate->id }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name: self.editName,
                    email: self.editEmail,
                    whatsapp: self.editWhatsapp,
                    commission_pct: self.editCommissionPct,
                    discount_pct: self.editDiscountPct,
                    notes: self.editNotes,
                }),
            })
            .then(r => r.json())
            .then(json => {
                if (json.ok) { self.showEdit = false; window.location.reload(); }
                else self.editError = json.message || 'Erro ao salvar.';
            })
            .catch(() => { self.editError = 'Erro de conexão.'; })
            .finally(() => { self.saving = false; });
        },

        markPaid() {
            const self = this;
            if (! confirm('Confirmar pagamento de todas as comissões pendentes?')) return;
            self.paying = true;
            fetch('/admin/affiliates/{{ $affiliate->id }}/pay', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(json => { if (json.ok) window.location.reload(); })
            .catch(err => alert('Erro: ' + err.message))
            .finally(() => { self.paying = false; });
        },
    };
}
</script>

</body>
</html>
