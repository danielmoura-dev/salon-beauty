@props(['label', 'error' => null])

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    {{ $slot }}
    @if ($error)
        <p class="mt-1 text-xs text-red-500">{{ $error }}</p>
    @endif
</div>