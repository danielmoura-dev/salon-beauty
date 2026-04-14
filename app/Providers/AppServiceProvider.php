<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
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

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Confirme seu e-mail — Salon Beauty')
                ->html(
                    view('emails.verify-email', [
                        'user'      => $notifiable,
                        'verifyUrl' => $url,
                    ])->render()
                );
        });
    }
}
