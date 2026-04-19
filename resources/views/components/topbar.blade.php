<header class="sticky top-0 z-10 flex items-center gap-4 bg-white border-b border-gray-100 px-4 py-3 lg:hidden will-change-transform">

    {{-- Botão hamburguer --}}
    <button @click="sidebarOpen = true"
        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 transition-colors">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>

    {{-- Logo centralizada no mobile --}}
    <div class="flex-1 flex justify-center">
        <img src="{{ asset('images/logo2.png') }}" alt="Salon Beauty" class="h-9 w-auto">
    </div>

    {{-- Avatar do usuário --}}
    @if (auth()->user()->avatar)
        <img src="{{ Storage::url(auth()->user()->avatar) }}"
             class="h-8 w-8 rounded-full object-cover" alt="Avatar">
    @else
        <div class="h-8 w-8 rounded-full bg-accent-100 flex items-center justify-center text-accent-600 font-semibold text-xs">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
    @endif
</header>