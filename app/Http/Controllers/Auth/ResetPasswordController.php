<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    public function showForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'token'    => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['token' => 'Link inválido ou expirado.']);
        }

        // Token válido por 60 minutos (created_at vem como string do query builder)
        if (Carbon::parse($record->created_at)->lt(now()->subMinutes(60))) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['token' => 'Link expirado. Solicite um novo.']);
        }

        // Resposta idêntica para e-mail inexistente e token errado (não revela quais e-mails estão cadastrados)
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return back()->withErrors(['token' => 'Link inválido ou expirado.']);
        }

        DB::transaction(function () use ($user, $request) {
            $user->forceFill([
                'password'       => Hash::make($request->password),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Quem recupera a conta espera que sessões abertas (possivelmente do invasor) sejam encerradas
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        return redirect()->route('login')->with('success', 'Senha redefinida com sucesso! Faça login.');
    }
}
