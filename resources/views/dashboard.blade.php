<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — Gestão Beauty</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 p-8">
    <h1 class="text-2xl font-bold text-gray-800">
        Olá, {{ auth()->user()->name }}!
    </h1>
    <p class="text-gray-500 mt-1">Bem-vindo ao Gestão Beauty. O dashboard completo vem na próxima etapa.</p>
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button class="text-sm text-rose-600 hover:underline">Sair</button>
    </form>
</body>
</html>