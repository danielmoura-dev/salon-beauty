<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $tenant = $user->tenant;

        return view('app.dashboard', compact('user', 'tenant'));
    }
}