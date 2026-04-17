<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant       = auth()->user()->tenant->load('subscription');
        $subscription = $tenant->subscription;

        return view('app.settings.index', compact('tenant', 'subscription'));
    }

    public function updateProfile(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'logo'  => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($tenant->logo) Storage::disk('public')->delete($tenant->logo);
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $tenant->update($data);

        return back()->with('success', 'Dados atualizados!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.confirmed'                => 'As senhas não conferem.',
        ]);

        auth()->user()->update([
            'password' => $request->password,
        ]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }

    public function updateAdvanced(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $data = $request->validate([
            'credit_card_fee'       => ['nullable', 'numeric', 'min:0', 'max:20'],
            'debit_card_fee'        => ['nullable', 'numeric', 'min:0', 'max:20'],
            'agenda_start_hour'     => ['required', 'integer', 'min:0', 'max:23'],
            'agenda_end_hour'       => ['required', 'integer', 'min:1', 'max:23'],
            'allow_duplicate_phone' => ['boolean'],
            'show_pending_orders'   => ['boolean'],
        ]);

        $data['allow_duplicate_phone'] = $request->boolean('allow_duplicate_phone');
        $data['show_pending_orders']   = $request->boolean('show_pending_orders');

        $tenant->update($data);

        return back()->with('success', 'Configurações salvas!');
    }
}