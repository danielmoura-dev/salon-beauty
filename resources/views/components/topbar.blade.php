<header class="sticky top-0 z-10 flex items-center gap-4 bg-white border-b border-gray-100 px-4 py-3 lg:hidden">

    {{-- Botão hamburguer --}}
    <button @click="sidebarOpen = true"
        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 transition-colors">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>

    {{-- Logo centralizada no mobile --}}
    <span class="flex-1 text-center font-bold text-accent-600 tracking-tight flex items-center justify-center gap-1.5">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 11-5.196 3 3 3 0 015.196-3zm1.536-.887a2.165 2.165 0 001.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863l2.077-1.199m0-3.328a4.323 4.323 0 012.068-1.379l5.325-1.628a4.5 4.5 0 012.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0010.607 12m3.736 0l7.794 4.5-.802.215a4.5 4.5 0 01-2.48-.043l-5.326-1.629a4.324 4.324 0 01-2.068-1.379M14.343 12l-2.882 1.664"/></svg>
        Gestão Beauty
    </span>

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