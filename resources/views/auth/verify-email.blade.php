@extends('layouts.auth')
@section('title', 'Confirme seu e-mail')
@section('heading', 'Confirme seu e-mail')

@section('content')
<div class="space-y-6">
    <p class="text-center text-gray-500 text-sm leading-relaxed">
        Enviamos um código de 6 dígitos para<br>
        <strong class="text-gray-700">{{ auth()->user()->email }}</strong>
    </p>

    @if(session('success'))
        <p class="text-center text-sm text-green-600 font-medium">{{ session('success') }}</p>
    @endif

    @error('code')
        <p class="text-center text-sm text-red-500">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('verification.verify') }}" id="codeForm">
        @csrf
        <input type="hidden" name="code" id="codeInput">

        <div class="flex justify-center gap-2 mb-6">
            @for ($i = 0; $i < 6; $i++)
                <input type="text"
                       inputmode="numeric"
                       maxlength="1"
                       class="code-digit w-10 h-12 sm:w-12 sm:h-14 text-center text-xl sm:text-2xl font-bold border-2 border-gray-300 rounded-xl
                              focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-colors
                              @error('code') border-red-400 @enderror"
                       autocomplete="off">
            @endfor
        </div>

        <button type="submit"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl
                       transition-colors focus:outline-none focus:ring-2 focus:ring-primary-400">
            Verificar conta
        </button>
    </form>

    <div class="text-center">
        <form method="POST" action="{{ route('verification.send') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-primary-600 hover:text-primary-800 font-medium">
                Reenviar código
            </button>
        </form>
        <span class="text-gray-300 mx-2">·</span>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-gray-400 hover:text-gray-600">Sair</button>
        </form>
    </div>
</div>

<script>
    const digits = document.querySelectorAll('.code-digit');
    const codeInput = document.getElementById('codeInput');
    const form = document.getElementById('codeForm');

    digits.forEach((el, i) => {
        el.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '').slice(-1);
            if (el.value && i < digits.length - 1) digits[i + 1].focus();
            syncCode();
        });

        el.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !el.value && i > 0) digits[i - 1].focus();
        });

        el.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
            pasted.split('').forEach((char, j) => {
                if (digits[j]) digits[j].value = char;
            });
            const next = Math.min(pasted.length, digits.length - 1);
            digits[next].focus();
            syncCode();
        });
    });

    function syncCode() {
        codeInput.value = Array.from(digits).map(d => d.value).join('');
    }

    digits[0].focus();
</script>
@endsection
