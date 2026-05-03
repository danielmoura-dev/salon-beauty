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
            ['route' => 'booking-link', 'icon' => 'link',     'label' => 'Meu Link'],
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

{{-- Suporte via WhatsApp --}}
@if (config('services.support.whatsapp_number'))
@php
    $waNumber  = config('services.support.whatsapp_number');
    $waMessage = rawurlencode(config('services.support.whatsapp_message', 'Olá! Preciso de ajuda com o Salon Beauty.'));
    $waUrl     = "https://wa.me/{$waNumber}?text={$waMessage}";
@endphp
<div class="px-3 pb-2">
    <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
              text-gray-600 hover:bg-green-50 hover:text-green-700 transition-colors">
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
        Suporte
    </a>
</div>
@endif

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
