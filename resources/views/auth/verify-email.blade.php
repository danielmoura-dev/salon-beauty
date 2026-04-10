@extends('layouts.auth')
@section('title', 'Verifique seu e-mail')
@section('heading', 'Verifique seu e-mail')

@section('content')
<div class="text-center space-y-4">
    <svg class="h-14 w-14 mx-auto text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
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