<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->registerWithTenant($request->validated());

        event(new Registered($user));  // dispara email de verificação via Resend

        auth()->login($user);

        return redirect()->route('verification.notice');
    }
}