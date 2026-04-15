@extends('layouts.auth')
@section('title', 'Recuperar senha — Salon Beauty')
@section('heading', 'Recuperar senha')

@section('content')
<p class="text-center text-sm text-gray-500 mb-6">
    Informe seu e-mail e enviaremos um link para redefinir sua senha.
</p>

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
            class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500
                   @error('email') border-red-400 @enderror">
        @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <button type="submit"
        class="w-full rounded-xl bg-primary-600 py-3 text-white font-semibold hover:bg-primary-700 transition-colors">
        Enviar link de recuperação
    </button>

    <p class="text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="text-primary-600 font-medium hover:underline">Voltar ao login</a>
    </p>
</form>
@endsection
