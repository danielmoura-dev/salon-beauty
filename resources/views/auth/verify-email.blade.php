@extends('layouts.auth')
@section('title', 'Verifique seu e-mail')
@section('heading', 'Verifique seu e-mail')

@section('content')
<div class="text-center space-y-4">
    <div class="text-5xl">📬</div>
    <p class="text-gray-600 text-sm leading-relaxed">
        Enviamos um link de confirmação para <strong>{{ auth()->user()->email }}</strong>.
        Clique no link para ativar sua conta.
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit"
            class="w-full rounded-xl border border-rose-300 py-2.5 text-rose-600 font-medium hover:bg-rose-50 transition-colors text-sm">
            Reenviar e-mail
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="text-sm text-gray-400 hover:text-gray-600">Sair</button>
    </form>
</div>
@endsection