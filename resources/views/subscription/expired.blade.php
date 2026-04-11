@extends('layouts.auth')
@section('title', 'Assinatura expirada')
@section('heading', 'Acesso suspenso')
@section('content')
<div class="text-center space-y-4">
    <svg class="h-14 w-14 mx-auto text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
    <p class="text-gray-600 text-sm">Seu período de teste expirou ou a assinatura foi cancelada.</p>
    <a href="{{ route('dashboard') }}" class="block w-full rounded-xl bg-primary-600 py-3 text-white font-semibold text-center hover:bg-primary-700">
        Ver planos
    </a>
</div>
@endsection