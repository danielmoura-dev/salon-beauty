<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSubscriptionIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->tenant->isActive()) {
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}