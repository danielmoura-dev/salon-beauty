@extends('layouts.auth')
@section('title', 'Criar Conta — Salon Beauty')
@section('heading', 'Crie sua conta grátis')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-4"
    x-data="registerForm()"
    @submit.prevent="handleSubmit($event)">
    @csrf

    {{-- Nome do proprietário --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Seu nome</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
            class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500
                   @error('name') border-red-400 @enderror">
        @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    {{-- Nome do estabelecimento --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do estabelecimento</label>
        <input type="text" name="business_name" value="{{ old('business_name') }}" required
            class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500
                   @error('business_name') border-red-400 @enderror">
        @error('business_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    {{-- Email --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
        <input type="email" name="email" value="{{ old('email') }}" required
            class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500
                   @error('email') border-red-400 @enderror">
        @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    {{-- Senha --}}
    <div x-data="{ show: false }">
        <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
        <div class="relative">
            <input :type="show ? 'text' : 'password'" name="password" required
                class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 pr-10
                       @error('password') border-red-400 @enderror">
            <button type="button" @click="show = !show"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
            </button>
        </div>
        @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    {{-- Confirmar senha --}}
    <div x-data="{ show: false }">
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar senha</label>
        <div class="relative">
            <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 pr-10">
            <button type="button" @click="show = !show"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
            </button>
        </div>
    </div>

    {{-- Código promocional (opcional) --}}
    <div>
        <button type="button" @click="codeOpen = !codeOpen"
            class="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span x-text="codeOpen ? 'Remover código promocional' : 'Tenho um código promocional'"></span>
        </button>
        <div x-show="codeOpen" x-transition class="mt-2 space-y-1">
            <div class="relative">
                <input type="text" name="affiliate_code" x-model="code"
                    @input="onCodeInput()"
                    placeholder="Ex: PARCEIRO2024"
                    :class="{
                        'border-red-400 focus:ring-red-400 focus:border-red-400': codeStatus === 'invalid',
                        'border-green-400 focus:ring-green-400 focus:border-green-400': codeStatus === 'valid',
                        'border-gray-300 focus:ring-primary-500 focus:border-primary-500': codeStatus === null || codeStatus === 'checking'
                    }"
                    class="w-full rounded-xl shadow-sm font-mono uppercase placeholder:normal-case placeholder:font-sans pr-9">

                {{-- Ícone de status --}}
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg x-show="codeStatus === 'checking'" class="h-4 w-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg x-show="codeStatus === 'valid'" class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="codeStatus === 'invalid'" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>

            <p x-show="codeStatus === null || codeStatus === 'checking'" class="text-xs text-gray-400">
                Se válido, você ganha desconto nos 3 primeiros meses.
            </p>
            <p x-show="codeStatus === 'valid'" class="text-xs text-green-600 font-medium">
                Código válido! Você ganha <span x-text="codeDiscount + '%'"></span> de desconto nos 3 primeiros meses.
            </p>
            <p x-show="codeStatus === 'invalid'" class="text-xs text-red-500 font-medium">
                Código inválido. Verifique o código ou deixe o campo em branco.
            </p>

            @error('affiliate_code')
            <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <button type="submit" :disabled="loading || codeStatus === 'invalid' || codeStatus === 'checking'"
        class="w-full rounded-xl bg-primary-600 py-3 text-white font-semibold hover:bg-primary-700 transition-colors disabled:opacity-70 flex items-center justify-center gap-2">
        <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span x-text="loading ? 'Criando conta…' : 'Criar conta grátis'"></span>
    </button>

    {{-- Divisor --}}
    <div class="relative my-2">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
        <div class="relative flex justify-center text-sm"><span class="bg-white px-3 text-gray-400">ou</span></div>
    </div>

    {{-- Google --}}
    <a href="{{ route('google.redirect') }}"
        class="flex items-center justify-center gap-3 w-full rounded-xl border border-gray-300 py-3
               text-gray-700 font-medium hover:bg-gray-50 transition-colors">
        <svg class="h-5 w-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Entrar com Google
    </a>

    <p class="text-center text-sm text-gray-500">
        Já tem conta? <a href="{{ route('login') }}" class="text-primary-600 font-medium hover:underline">Entrar</a>
    </p>
</form>

<script>
function registerForm() {
    return {
        loading: false,
        codeOpen: {{ old('affiliate_code') ? 'true' : 'false' }},
        code: '{{ old('affiliate_code', '') }}',
        codeStatus: @json(old('affiliate_code') ? (
            \App\Models\Affiliate::where('code', strtoupper(old('affiliate_code')))->where('is_active', true)->exists() ? 'valid' : 'invalid'
        ) : null),
        codeDiscount: null,
        _timer: null,

        onCodeInput() {
            this.code = this.code.toUpperCase();
            this.codeStatus = null;
            clearTimeout(this._timer);
            if (!this.code.trim()) return;
            this.codeStatus = 'checking';
            this._timer = setTimeout(() => this.checkCode(), 600);
        },

        async checkCode() {
            if (!this.code.trim()) { this.codeStatus = null; return; }
            try {
                const res  = await fetch('/affiliate-code/check?code=' + encodeURIComponent(this.code));
                const json = await res.json();
                this.codeStatus  = json.valid ? 'valid' : 'invalid';
                this.codeDiscount = json.discount_pct ?? null;
            } catch {
                this.codeStatus = null;
            }
        },

        handleSubmit(e) {
            if (this.codeStatus === 'invalid' || this.codeStatus === 'checking') return;
            this.loading = true;
            e.target.submit();
        },
    };
}
</script>
@endsection