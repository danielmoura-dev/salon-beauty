<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// --- Rotas públicas ---
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

// --- Verificação de email (Laravel built-in) ---
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', fn() => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('onboarding');
    })->middleware('signed')->name('verification.verify');
    Route::post('/email/resend', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Link de verificação reenviado!');
    })->middleware('throttle:6,1')->name('verification.send');
});

// --- Onboarding (autenticado + email verificado) ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});

// --- App principal (autenticado + verificado + assinatura ativa) ---
Route::middleware(['auth', 'verified', 'subscription.active'])->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
    // demais rotas serão adicionadas nos próximos módulos
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/subscription/expired', fn() => view('subscription.expired'))->name('subscription.expired');