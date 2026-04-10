@extends('layouts.auth')
@section('title', 'Nova senha — Gestão Beauty')
@section('heading', 'Criar nova senha')

@section('content')
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">

    @error('token')
        <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
        <input type="email" value="{{ $email }}" disabled
            class="w-full rounded-xl border-gray-200 bg-gray-50 text-gray-400 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
        <input type="password" name="password" required autofocus
            class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-rose-500 focus:border-rose-500
                   @error('password') border-red-400 @enderror">
        @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
        <input type="password" name="password_confirmation" required
            class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-rose-500 focus:border-rose-500">
    </div>

    <button type="submit"
        class="w-full rounded-xl bg-rose-600 py-3 text-white font-semibold hover:bg-rose-700 transition-colors">
        Redefinir senha
    </button>

    <p class="text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="text-rose-600 font-medium hover:underline">Voltar ao login</a>
    </p>
</form>
@endsection
