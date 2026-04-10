<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gestão Beauty')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full"
      x-data="{ sidebarOpen: false, isDesktop: window.innerWidth >= 1024 }"
      @resize.window="isDesktop = window.innerWidth >= 1024; if (isDesktop) sidebarOpen = false"
      @keydown.escape="sidebarOpen = false">

    {{-- ========== OVERLAY (mobile) ========== --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-20 bg-black/40 lg:hidden"
        style="display:none"
    ></div>

    {{-- ========== WRAPPER FLEX ========== --}}
    <div class="flex h-screen overflow-hidden">

    {{-- ========== SIDEBAR DRAWER (mobile) + SIDEBAR FIXA (desktop) ========== --}}
    <aside
        x-show="isDesktop || sidebarOpen"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        :class="isDesktop ? 'relative z-auto shadow-none flex-shrink-0' : 'fixed inset-y-0 left-0 z-30 shadow-sm'"
        class="w-64 flex flex-col bg-white border-r border-gray-100"
        style="display:none"
        x-cloak
    >
        @include('components.sidebar')
    </aside>

    {{-- ========== ÁREA PRINCIPAL ========== --}}
    <div class="flex flex-col flex-1 min-w-0 overflow-auto">

        {{-- Topbar mobile --}}
        @include('components.topbar')

        {{-- Conteúdo da página --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>{{-- fim área principal --}}

    </div>{{-- fim wrapper flex --}}

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>