<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail(mixed $notifiable): MailMessage
    {
        $verifyUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirme seu e-mail — Salon Beauty')
            ->html(
                view('emails.verify-email', [
                    'user'      => $notifiable,
                    'verifyUrl' => $verifyUrl,
                ])->render()
            );
    }
}
