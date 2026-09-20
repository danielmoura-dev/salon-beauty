<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        App::setLocale('pt_BR');
        \Carbon\Carbon::setLocale('pt_BR');

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Login: o bloqueio por e-mail+IP fica no LoginController; aqui só o teto por IP
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(30)->by($r->ip()));

        RateLimiter::for('register', fn (Request $r) => Limit::perHour(20)->by($r->ip()));

        // Recuperação de senha: evita e-mail bombing contra uma caixa postal
        RateLimiter::for('password-reset', fn (Request $r) => [
            Limit::perMinute(5)->by($r->ip()),
            Limit::perHour(5)->by('email:' . strtolower((string) $r->input('email'))),
        ]);

        // Código de verificação de e-mail (6 dígitos): impede força bruta
        RateLimiter::for('email-verify', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));

        // Página pública de agendamento (sem login)
        RateLimiter::for('booking-auth', fn (Request $r) => [
            Limit::perMinute(10)->by($r->ip()),
            Limit::perHour(60)->by($r->ip()),
        ]);
        RateLimiter::for('booking-write', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('booking-read', fn (Request $r) => Limit::perMinute(90)->by($r->ip()));
    }
}
