{{-- Logo --}}
<div class="flex items-center gap-2 px-5 py-5 border-b border-gray-100">
    <svg class="h-6 w-6 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 11-5.196 3 3 3 0 015.196-3zm1.536-.887a2.165 2.165 0 001.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863l2.077-1.199m0-3.328a4.323 4.323 0 012.068-1.379l5.325-1.628a4.5 4.5 0 012.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0010.607 12m3.736 0l7.794 4.5-.802.215a4.5 4.5 0 01-2.48-.043l-5.326-1.629a4.324 4.324 0 01-2.068-1.379M14.343 12l-2.882 1.664"/></svg>
    <span class="text-lg font-bold text-rose-600 leading-tight">Gestão<br><span class="font-light text-gray-500 text-sm">Beauty</span></span>

    {{-- Botão fechar (mobile only) --}}
    <button @click="sidebarOpen = false" class="ml-auto text-gray-400 hover:text-gray-600 lg:hidden">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

{{-- Perfil resumido --}}
<div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
    @if (auth()->user()->avatar)
        <img src="{{ Storage::url(auth()->user()->avatar) }}"
             class="h-9 w-9 rounded-full object-cover" alt="Avatar">
    @else
        <div class="h-9 w-9 rounded-full bg-rose-100 flex items-center justify-center text-rose-500 font-semibold text-sm">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
    @endif
    <div class="min-w-0">
        <p class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->tenant->name }}</p>
    </div>
</div>

{{-- Navegação --}}
<nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">

    @php
        $navItems = [
            ['route' => 'dashboard',    'icon' => 'home',     'label' => 'Dashboard'],
            ['route' => 'agenda',       'icon' => 'calendar', 'label' => 'Agenda'],
            ['route' => 'orders',       'icon' => 'receipt',  'label' => 'Comandas'],
            ['route' => 'clients',      'icon' => 'users',    'label' => 'Clientes'],
            ['route' => 'professionals','icon' => 'badge',    'label' => 'Profissionais'],
            ['route' => 'services',     'icon' => 'scissors', 'label' => 'Serviços'],
            ['route' => 'products',     'icon' => 'box',      'label' => 'Produtos'],
            ['route' => 'expenses',     'icon' => 'wallet',   'label' => 'Despesas'],
            ['route' => 'reports',      'icon' => 'chart',    'label' => 'Relatórios'],
            ['route' => 'settings',     'icon' => 'gear',     'label' => 'Configurações'],
        ];
    @endphp

    @foreach ($navItems as $item)
        @php $active = request()->routeIs($item['route'] . '*'); @endphp
        <a href="{{ route($item['route']) }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ $active
                      ? 'bg-rose-50 text-rose-600'
                      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">

            <x-sidebar-icon :name="$item['icon']" :active="$active" />
            {{ $item['label'] }}

            @if ($active)
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-rose-500"></span>
            @endif
        </a>
    @endforeach
</nav>

{{-- Rodapé: Logout --}}
<div class="px-3 py-4 border-t border-gray-100">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
            class="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                   text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
            </svg>
            Sair
        </button>
    </form>
</div>