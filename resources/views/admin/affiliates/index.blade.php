<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Afiliados — Admin Salon Beauty</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #0a0a0f; }
        .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.08); }
        .glow-violet { box-shadow: 0 0 24px rgba(139, 92, 246, 0.25); }
        .badge-active  { background: rgba(16,185,129,.15); color: #34d399; border: 1px solid rgba(16,185,129,.3); }
        .badge-inactive { background: rgba(239,68,68,.15); color: #f87171; border: 1px solid rgba(239,68,68,.3); }
        .row-hover:hover { background: rgba(255,255,255,0.03); }
    </style>
</head>
<body class="min-h-screen font-sans antialiased text-gray-100" x-data="affiliatesPage()">

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
            <div class="flex items-center gap-2.5 mb-3">
                <div class="w-7 h-7 rounded-full bg-violet-700 flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <p class="text-gray-300 text-xs truncate">{{ auth()->user()->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-xs text-gray-500 hover:text-gray-300 transition text-left px-1">→ Sair</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="flex items-center justify-between px-8 py-4 border-b border-white/[0.06]" style="background:#0f0f1a">
            <div>
                <h1 class="text-white font-semibold text-lg">Afiliados</h1>
                <p class="text-gray-500 text-xs">Gerencie códigos promocionais e comissões</p>
            </div>
            <button @click="showCreate = true" class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Novo Afiliado
            </button>
        </header>

        <main class="flex-1 px-8 py-7 space-y-7 overflow-auto">

            @if (session('success'))
            <div class="glass rounded-xl px-5 py-3 border-emerald-500/30 text-emerald-400 text-sm">
                {{ session('success') }}
            </div>
            @endif

            {{-- KPIs --}}
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                @php
                    $kpis = [
                        ['label' => 'Total de afiliados',  'value' => $stats['total_affiliates'],  'color' => 'text-white',      'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['label' => 'Afiliados ativos',    'value' => $stats['active_affiliates'], 'color' => 'text-emerald-400', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Total de indicados',  'value' => $stats['total_referred'],    'color' => 'text-violet-400',  'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5'],
                        ['label' => 'Comissões pendentes', 'value' => 'R$ ' . number_format($stats['total_pending'], 2, ',', '.'), 'color' => 'text-amber-400', 'small' => true, 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
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

            {{-- Tabela de afiliados --}}
            <div class="glass rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-white/[0.06]">
                    <h2 class="text-white font-semibold text-sm">Todos os Afiliados</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/[0.06]">
                                @foreach(['Afiliado','Código','Comissão','Desconto','Indicados','Pagantes','Pendente','Total pago','Status','Ações'] as $h)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            @forelse ($affiliates as $affiliate)
                            <tr class="row-hover transition-colors" x-data="affiliateRow('{{ $affiliate->id }}', {{ $affiliate->is_active ? 'true' : 'false' }})">
                                {{-- Afiliado --}}
                                <td class="px-4 py-3.5">
                                    <p class="font-medium text-white text-sm">{{ $affiliate->name }}</p>
                                    <p class="text-gray-500 text-xs">{{ $affiliate->email }}</p>
                                </td>

                                {{-- Código --}}
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-violet-300 bg-violet-900/30 px-2 py-0.5 rounded text-xs">{{ $affiliate->code }}</span>
                                        <button @click="copyCode('{{ $affiliate->code }}')" class="text-gray-600 hover:text-gray-300 transition-colors" title="Copiar código">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </td>

                                {{-- Comissão --}}
                                <td class="px-4 py-3.5 text-gray-300 font-mono text-xs">{{ number_format($affiliate->commission_pct, 1) }}%</td>

                                {{-- Desconto --}}
                                <td class="px-4 py-3.5 text-gray-300 font-mono text-xs">{{ number_format($affiliate->discount_pct, 1) }}% / 3 meses</td>

                                {{-- Indicados --}}
                                <td class="px-4 py-3.5 text-gray-300 font-mono text-sm text-center">{{ $affiliate->tenants_count }}</td>

                                {{-- Pagantes --}}
                                <td class="px-4 py-3.5 text-emerald-400 font-mono text-sm text-center">{{ $affiliate->paying_tenants_count }}</td>

                                {{-- Pendente --}}
                                <td class="px-4 py-3.5 text-amber-400 font-mono text-xs">R$ {{ number_format($affiliate->pending_commission, 2, ',', '.') }}</td>

                                {{-- Total pago --}}
                                <td class="px-4 py-3.5 text-gray-400 font-mono text-xs">R$ {{ number_format($affiliate->total_commission - $affiliate->pending_commission, 2, ',', '.') }}</td>

                                {{-- Status --}}
                                <td class="px-4 py-3.5">
                                    <span :class="isActive ? 'badge-active' : 'badge-inactive'" class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span x-text="isActive ? 'Ativo' : 'Inativo'"></span>
                                    </span>
                                </td>

                                {{-- Ações --}}
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.affiliates.show', $affiliate) }}" class="text-violet-400 hover:text-violet-300 text-xs transition-colors">Detalhes</a>
                                        <span class="text-gray-700">|</span>
                                        <button @click="toggleActive()" class="text-xs transition-colors" :class="isActive ? 'text-red-400 hover:text-red-300' : 'text-emerald-400 hover:text-emerald-300'">
                                            <span x-text="isActive ? 'Desativar' : 'Ativar'"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16 text-center text-gray-600">
                                    Nenhum afiliado cadastrado ainda.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

{{-- Modal: Criar Afiliado --}}
<div x-show="showCreate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.7)">
    <div class="glass rounded-2xl w-full max-w-md p-6 space-y-5" @click.outside="showCreate = false">
        <div class="flex items-center justify-between">
            <h3 class="text-white font-semibold text-base">Novo Afiliado</h3>
            <button @click="showCreate = false" class="text-gray-500 hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.affiliates.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Nome</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">E-mail</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Código Promocional <span class="text-gray-600">(maiúsculas, sem espaços)</span></label>
                <input type="text" name="code" required value="{{ old('code') }}" placeholder="EX: PARCEIRO2024"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white font-mono placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-violet-500"
                    oninput="this.value = this.value.toUpperCase()">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Comissão do afiliado (%)</label>
                    <input type="number" name="commission_pct" required value="{{ old('commission_pct', 10) }}" min="0.01" max="100" step="0.01"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-violet-500">
                    <p class="text-gray-600 text-xs mt-1">% que ele recebe por mês</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Desconto do cupom (%)</label>
                    <input type="number" name="discount_pct" required value="{{ old('discount_pct', 10) }}" min="0.01" max="100" step="0.01"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-violet-500">
                    <p class="text-gray-600 text-xs mt-1">% de desconto por 3 meses</p>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Observações <span class="text-gray-600">(opcional)</span></label>
                <textarea name="notes" rows="2"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-violet-500 resize-none">{{ old('notes') }}</textarea>
            </div>

            @if ($errors->any())
            <div class="text-red-400 text-xs space-y-0.5">
                @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
                @endforeach
            </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button type="button" @click="showCreate = false" class="flex-1 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium py-2.5 rounded-lg transition-colors">Cancelar</button>
                <button type="submit" class="flex-1 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium py-2.5 rounded-lg transition-colors">Criar Afiliado</button>
            </div>
        </form>
    </div>
</div>

<script>
function affiliatesPage() {
    return {
        showCreate: {{ $errors->any() ? 'true' : 'false' }},
        copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                // silent success
            });
        }
    };
}

function affiliateRow(id, initialActive) {
    return {
        id: id,
        isActive: initialActive,

        toggleActive() {
            const self = this;
            fetch('/admin/affiliates/' + self.id + '/toggle', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(json => { if (json.ok) self.isActive = json.is_active; })
            .catch(err => alert('Erro: ' + err.message));
        }
    };
}
</script>

</body>
</html>
