@props(['name', 'title'])

<div
    x-data="{ open: false }"
    x-on:open-modal-{{ $name }}.window="open = true"
    x-on:close-modal-{{ $name }}.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
    style="display:none"
>
    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

    {{-- Painel --}}
    <div
        class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl p-6 space-y-4
               max-h-[90vh] overflow-y-auto"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        @click.stop
    >
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        {{ $slot }}
    </div>
</div>