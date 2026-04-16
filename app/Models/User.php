<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasUuids, Notifiable;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'google_id',
        'avatar', 'phone', 'role', 'onboarding_completed', 'email_verified_at',
        'email_verification_code', 'email_verification_code_expires_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'                  => 'datetime',
        'email_verification_code_expires_at' => 'datetime',
        'password'                           => 'hashed',
        'onboarding_completed'               => 'boolean',
        'is_admin'                           => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function generateVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'email_verification_code'             => $code,
            'email_verification_code_expires_at'  => now()->addMinutes(60),
        ]);
        return $code;
    }

    public function verifyCode(string $code): bool
    {
        return $this->email_verification_code === $code
            && $this->email_verification_code_expires_at?->isFuture();
    }

    public function sendEmailVerificationNotification(): void
    {
        $code = $this->generateVerificationCode();

        Mail::send([], [], function ($message) use ($code) {
            $message
                ->to($this->email, $this->name)
                ->subject('Confirme seu e-mail — Salon Beauty')
                ->html(view('emails.verify-email', [
                    'user' => $this,
                    'code' => $code,
                ])->render());
        });
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}