<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Resposta genérica para não revelar se o e-mail existe
        if (! $user) {
            return back()->with('success', 'Se esse e-mail estiver cadastrado, você receberá um link em instantes.');
        }

        // Gera token e salva (invalidando qualquer anterior)
        $token = Str::random(64);

        DB::table('password_reset_tokens')->upsert(
            [
                'email'      => $user->email,
                'token'      => Hash::make($token),
                'created_at' => now(),
            ],
            ['email'],
        );

        $link = url("/password/reset/{$token}?email=" . urlencode($user->email));

        Mail::send([], [], function ($message) use ($user, $link) {
            $message
                ->to($user->email, $user->name)
                ->subject('Redefinição de senha — Salon Beauty')
                ->html(view('emails.password-reset', ['user' => $user, 'link' => $link])->render());
        });

        return back()->with('success', 'Se esse e-mail estiver cadastrado, você receberá um link em instantes.');
    }
}
