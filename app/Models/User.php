<?php

namespace App\Models;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasUuids, Notifiable;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'google_id',
        'avatar', 'phone', 'role', 'onboarding_completed', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'     => 'datetime',
        'password'              => 'hashed',
        'onboarding_completed'  => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
        );

        Mail::send([], [], function ($message) use ($verifyUrl) {
            $message
                ->to($this->email, $this->name)
                ->subject('Confirme seu e-mail — Salon Beauty')
                ->html(view('emails.verify-email', [
                    'user'      => $this,
                    'verifyUrl' => $verifyUrl,
                ])->render());
        });
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}