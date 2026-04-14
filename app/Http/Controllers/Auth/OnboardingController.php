<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OnboardingController extends Controller
{
    public function show()
    {
        if (auth()->user()->onboarding_completed) {
            return redirect()->route('dashboard');
        }
        return view('auth.onboarding');
    }

    public function complete(Request $request)
    {
        $request->validate([
            'phone'  => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = auth()->user();
        $data = ['onboarding_completed' => true];

        if ($request->filled('phone')) {
            $data['phone'] = $request->phone;
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store("avatars/{$user->tenant_id}", 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return redirect()->route('dashboard')->with('success', 'Bem-vindo ao Salon Beauty!');
    }
}