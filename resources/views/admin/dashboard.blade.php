<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — Salon Beauty</title>
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
<body class="min-h-screen font-sans antialiased text-gray-100" x-data="adminPanel()">

{{-- ───── Sidebar ───── --}}
<div class="flex min-h-screen">
    <aside class="w-56 shrink-0 flex flex-col border-r border-white/[0.06]" style="background:#0f0f1a">
        {{-- Logo --}}
        <div class="px-5 py-6 border-b border-white/[0.06]">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-700 flex items-center justify-center text-white font-bold text-sm shadow-lg glow-violet">S</div>
                <div>
                    <p class="text-white font-semibold text-sm leading-none">SalonBeauty</p>
                    <p class="text-violet-400 text-xs mt-0.5">Admin Console</p>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-violet-600/20 text-violet-300 text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.affiliates.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 text-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Afiliados
            </a>
        </nav>

        {{-- User --}}
        <div class="px-4 py-4 border-t border-white/[0.06]">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="w-7 h-7 rounded-full bg-violet-700 flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <p class="text-gray-300 text-xs truncate">{{ auth()->user()->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-xs text-gray-500 hover:text-gray-300 transition text-left px-1">
                    → Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- ───── Main content ───── --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Top bar --}}
        <header class="flex items-center justify-between px-8 py-4 border-b border-white/[0.06]" style="background:#0f0f1a">
            <div>
                <h1 class="text-white font-semibold text-lg">Visão Geral</h1>
                <p class="text-gray-500 text-xs">{{ now()->format('d \d\e F \d\e Y') }}</p>
            </div>
        </header>

        <main class="flex-1 px-8 py-7 space-y-7 overflow-auto">

            {{-- KPI row --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">
                @php
                    $kpis = [
                        ['label' => 'Total de tenants',  'value' => $stats['total_tenants'],               'color' => 'text-white',       'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['label' => 'Tenants ativos',    'value' => $stats['active_tenants'],              'color' => 'text-emerald-400',  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Em trial',          'value' => $stats['trial_tenants'],               'color' => 'text-amber-400',    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Pagos',             'value' => $stats['paid_tenants'],                'color' => 'text-violet-400',   'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        ['label' => 'Receita total',     'value' => 'R$ ' . number_format($stats['total_revenue'], 2, ',', '.'), 'color' => 'text-white', 'small' => true, 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                @endphp

                @foreach ($kpis as $kpi)
                <div class="glass rounded-xl p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <p class="text-gray-400 text-xs font-medium tracking-wide uppercase">{{ $kpi['label'] }}</p>
                        <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $kpi['icon'] }}"/>
                            </svg>
                        </div>
                    </div>
                    <p class="{{ $kpi['color'] }} font-bold {{ ($kpi['small'] ?? false) ? 'text-xl' : 'text-3xl' }}">{{ $kpi['value'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Tenants table --}}
            <div class="glass rounded-xl overflow-hidden">
                {{-- Table header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                    <h2 class="text-white font-semibold text-sm">Todos os Tenants</h2>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Buscar…"
                            class="bg-white/5 border border-white/10 rounded-lg pl-8 pr-4 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-violet-500 w-52"
                        >
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/[0.06]">
                                @foreach(['Negócio','E-mail','Status','Trial até','Assinatura','Clientes','Profis.','Comandas','Receita','Estender trial'] as $h)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap
                                    {{ in_array($h, ['Clientes','Profis.','Comandas']) ? 'text-center' : '' }}
                                    {{ in_array($h, ['Receita']) ? 'text-right' : '' }}
                                ">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            @foreach ($tenants as $tenant)
                            <tr
                                class="row-hover transition-colors"
                                x-show="matchesTenant('{{ strtolower($tenant->name . ' ' . $tenant->slug) }}')"
                                x-data="tenantRow('{{ $tenant->id }}')"
                            >
                                {{-- Negócio --}}
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-600/40 to-purple-800/40 flex items-center justify-center text-violet-300 font-bold text-xs shrink-0 border border-violet-500/20">
                                            {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-white text-sm leading-none">{{ $tenant->name }}</p>
                                            <p class="text-gray-600 text-xs mt-0.5">{{ $tenant->created_at->format('d/m/Y') }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- E-mail --}}
                                <td class="px-4 py-3.5 text-gray-400 text-xs">
                                    @php
                                        [$local, $domain] = explode('@', $tenant->email);
                                        $masked = substr($local, 0, 3) . str_repeat('*', max(strlen($local) - 3, 3)) . '@' . $domain;
                                    @endphp
                                    {{ $masked }}
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3.5">
                                    @php
                                        $cls = match($tenant->plan_status) {
                                            'active'   => 'badge-active',
                                            'trial'    => 'badge-trial',
                                            default    => 'badge-expired',
                                        };
                                        $lbl = match($tenant->plan_status) {
                                            'active'   => 'Ativo',
                                            'trial'    => 'Trial',
                                            'canceled' => 'Cancelado',
                                            'expired'  => 'Expirado',
                                            default    => $tenant->plan_status,
                                        };
                                    @endphp
                                    <span class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full {{ $cls }}">{{ $lbl }}</span>
                                </td>

                                {{-- Trial até --}}
                                <td class="px-4 py-3.5 text-xs">
                                    @if ($tenant->trial_ends_at)
                                        @php
                                            $daysLeft = (int) today()->diffInDays($tenant->trial_ends_at->copy()->startOfDay(), false);
                                        @endphp
                                        <span id="trial-{{ $tenant->id }}" class="font-mono text-gray-400">{{ $tenant->trial_ends_at->format('d/m/Y') }}</span>
                                        <span class="block mt-0.5 font-medium {{ $daysLeft > 7 ? 'text-emerald-400' : ($daysLeft > 0 ? 'text-amber-400' : 'text-red-400') }}">
                                            {{ $daysLeft > 0 ? $daysLeft . ' dias restantes' : 'Expirado' }}
                                        </span>
                                    @else
                                        <span id="trial-{{ $tenant->id }}" class="text-gray-700">—</span>
                                    @endif
                                </td>

                                {{-- Assinatura --}}
                                <td class="px-4 py-3.5 text-xs">
                                    @if ($tenant->subscription)
                                        <p class="text-gray-300 font-medium capitalize">{{ $tenant->subscription->gateway }}</p>
                                        <p class="text-gray-500">{{ $tenant->subscription->status }}</p>
                                        @if ($tenant->subscription->current_period_end)
                                            <p class="text-gray-600 font-mono">{{ $tenant->subscription->current_period_end->format('d/m/Y') }}</p>
                                        @endif
                                    @else
                                        <span class="text-gray-700">—</span>
                                    @endif
                                </td>

                                {{-- Clientes --}}
                                <td class="px-4 py-3.5 text-center">
                                    <span class="text-gray-300 font-mono text-sm">{{ $tenant->client_count }}</span>
                                </td>

                                {{-- Profis. --}}
                                <td class="px-4 py-3.5 text-center">
                                    <span class="text-gray-300 font-mono text-sm">{{ $tenant->professional_count }}</span>
                                </td>

                                {{-- Comandas --}}
                                <td class="px-4 py-3.5 text-center">
                                    <span class="text-gray-300 font-mono text-sm">{{ $tenant->order_count }}</span>
                                </td>

                                {{-- Receita --}}
                                <td class="px-4 py-3.5 text-right text-xs font-mono text-gray-300">
                                    R$ {{ number_format($tenant->revenue, 2, ',', '.') }}
                                </td>

                                {{-- Estender trial --}}
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-gray-600 text-xs">+</span>
                                            <input
                                                type="number"
                                                x-model.number="extendDays"
                                                min="1" max="365"
                                                title="Dias a adicionar ao trial"
                                                class="w-12 bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-center text-white focus:outline-none focus:ring-1 focus:ring-violet-500"
                                            >
                                            <span class="text-gray-600 text-xs">dias</span>
                                            <button
                                                @click="extendTrial()"
                                                :disabled="extending"
                                                class="relative flex items-center gap-1 bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                                            >
                                                <span x-show="!extending && !saved">+ dias</span>
                                                <span x-show="extending" class="flex items-center gap-1">
                                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                                <span x-show="saved" class="text-emerald-300">✓ salvo</span>
                                            </button>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($tenants->isEmpty())
                <div class="px-6 py-16 text-center text-gray-600">
                    <svg class="w-8 h-8 mx-auto mb-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    Nenhum tenant encontrado.
                </div>
                @endif
            </div>

        </main>
    </div>
</div>

<script>
function adminPanel() {
    return {
        search: '',
        matchesTenant(haystack) {
            if (!this.search) return true;
            return haystack.includes(this.search.toLowerCase());
        }
    }
}

function tenantRow(tenantId) {
    return {
        tenantId:   tenantId,
        extendDays: 7,
        extending:  false,
        saved:      false,

        extendTrial() {
            const self = this;
            self.extending = true;

            fetch('/admin/tenants/' + self.tenantId + '/trial', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ days: parseInt(self.extendDays) }),
            })
            .then(function(res)  { return res.json(); })
            .then(function(json) {
                if (json.ok) {
                    // Atualiza o DOM diretamente — sem depender de reatividade Alpine
                    var span = document.getElementById('trial-' + self.tenantId);
                    if (span) span.textContent = json.trial_ends_at;
                    self.saved = true;
                    setTimeout(function() { self.saved = false; }, 2500);
                } else {
                    alert('Falha: ' + (json.message || 'erro desconhecido'));
                }
            })
            .catch(function(err) { alert('Erro: ' + err.message); })
            .finally(function()  { self.extending = false; });
        }
    };
}
</script>

</body>
</html>
