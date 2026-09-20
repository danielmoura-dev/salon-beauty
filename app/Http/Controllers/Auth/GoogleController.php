<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Falha na autenticação com Google.']);
        }

        try {
            $user = $this->authService->findOrCreateFromGoogle($socialUser);
        } catch (\DomainException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        auth()->login($user, true);
        request()->session()->regenerate();

        if (! $user->email_verified_at) {
            $user->markEmailAsVerified();
        }

        if (! $user->onboarding_completed) {
            return redirect()->route('onboarding');
        }

        return redirect()->route('dashboard');
    }
}