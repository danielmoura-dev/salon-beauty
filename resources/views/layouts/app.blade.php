<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gestão Beauty')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta name="mobile-web-app-capable" content="no">
    <meta name="apple-mobile-web-app-capable" content="no">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Sidebar visível imediatamente no desktop — antes do Alpine carregar */
        @media (min-width: 1024px) {
            #main-sidebar { display: flex !important; }
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full"
      x-data="{ sidebarOpen: false, isDesktop: window.innerWidth >= 1024 }"
      @resize.window="isDesktop = window.innerWidth >= 1024; if (isDesktop) sidebarOpen = false"
      @keydown.escape="sidebarOpen = false">

    {{-- Overlay mobile --}}
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

    {{-- ========== SIDEBAR ========== --}}
    <aside
        id="main-sidebar"
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
    >
        @include('components.sidebar')
    </aside>

    {{-- ========== ÁREA PRINCIPAL ========== --}}
    <div class="flex flex-col flex-1 min-w-0 overflow-auto">

        @include('components.topbar')

        <main class="flex-1 p-4 sm:p-6 lg:p-8">

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
    </div>

    </div>

    {{-- ========== MODAL DE CONFIRMAÇÃO GLOBAL ========== --}}
    <div x-data="{
            show: false,
            message: '',
            formId: null,
            open(detail) { this.message = detail.message; this.formId = detail.formId; this.show = true; },
            confirm() { document.getElementById(this.formId)?.submit(); this.show = false; }
         }"
         @open-confirm.window="open($event.detail)"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="show = false"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl p-6 space-y-4"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Confirmar exclusão</h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="message"></p>
                </div>
            </div>
            <div class="flex gap-3 pt-1">
                <button @click="show = false"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button @click="confirm()"
                    class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                    Excluir
                </button>
            </div>
        </div>
    </div>

</body>
</html>
