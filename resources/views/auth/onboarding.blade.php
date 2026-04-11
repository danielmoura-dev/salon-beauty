@extends('layouts.auth')
@section('title', 'Configuração inicial')
@section('heading', 'Quase lá!')

@section('content')
<p class="text-center text-sm text-gray-500 mb-6">
    Adicione alguns detalhes para personalizar sua conta. Você pode pular e fazer isso depois.
</p>

<form method="POST" action="{{ route('onboarding.complete') }}" enctype="multipart/form-data" class="space-y-5">
    @csrf

    {{-- Foto de perfil --}}
    <div class="flex flex-col items-center gap-3">
        <div id="avatar-preview"
            class="h-20 w-20 rounded-full bg-primary-100 flex items-center justify-center text-primary-400 text-3xl overflow-hidden">
            <svg class="h-10 w-10 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
        </div>
        <label class="cursor-pointer text-sm text-primary-600 font-medium hover:underline">
            Adicionar foto de perfil
            <input type="file" name="avatar" accept="image/*" class="hidden"
                onchange="previewAvatar(this)">
        </label>
        @error('avatar')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    {{-- WhatsApp --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp (opcional)</label>
        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="(85) 99999-9999"
            class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">
        @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <button type="submit"
        class="w-full rounded-xl bg-primary-600 py-3 text-white font-semibold hover:bg-primary-700 transition-colors">
        Concluir configuração
    </button>

    <form method="POST" action="{{ route('onboarding.complete') }}">
        @csrf
        <button type="submit" class="w-full text-sm text-gray-400 hover:text-gray-600 py-1">
            Pular por agora
        </button>
    </form>
</form>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('avatar-preview');
            preview.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-cover">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection