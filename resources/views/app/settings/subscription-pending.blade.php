@extends('layouts.app')
@section('title', 'Processando pagamento…')

@section('content')
<div class="max-w-sm mx-auto text-center py-16 space-y-4">
    <div class="text-5xl">⏳</div>
    <h1 class="text-xl font-bold text-gray-900">Processando seu pagamento</h1>
    <p class="text-gray-500 text-sm">
        Estamos aguardando a confirmação do pagamento.
        Isso pode levar alguns segundos. Sua conta será ativada automaticamente.
    </p>
    <a href="{{ route('dashboard') }}"
       class="inline-block rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white hover:bg-primary-700">
        Voltar ao Dashboard
    </a>
</div>
@endsection