@extends('layouts.auth')
@section('title', 'Assinatura expirada')
@section('heading', 'Acesso suspenso')
@section('content')
<div class="text-center space-y-4">
    <div class="text-5xl">🔒</div>
    <p class="text-gray-600 text-sm">Seu período de teste expirou ou a assinatura foi cancelada.</p>
    <a href="{{ route('dashboard') }}" class="block w-full rounded-xl bg-rose-600 py-3 text-white font-semibold text-center hover:bg-rose-700">
        Ver planos
    </a>
</div>
@endsection