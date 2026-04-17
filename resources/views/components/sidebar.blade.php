{{-- Logo --}}
<div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100">
    <img src="{{ asset('images/logo2.png') }}" alt="Salon Beauty" class="h-14 w-auto">

    <button @click="sidebarOpen = false" class="ml-auto text-gray-400 hover:text-gray-600 lg:hidden">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

{{-- Perfil resumido --}}
<div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
    @if (auth()->user()->tenant->logo)
        <img src="{{ Storage::url(auth()->user()->tenant->logo) }}"
             class="h-9 w-9 rounded-full object-cover" alt="Logo">
    @else
        <div class="h-9 w-9 rounded-full bg-accent-100 flex items-center justify-center text-accent-600 font-semibold text-sm">
            {{ strtoupper(substr(auth()->user()->tenant->name, 0, 1)) }}
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
                      ? 'bg-accent-50 text-accent-600'
                      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">

            <x-sidebar-icon :name="$item['icon']" :active="$active" />
            {{ $item['label'] }}

            @if ($active)
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-accent-500"></span>
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
