<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSubscriptionIsActive
{
    // Prefixos de rota que podem escrever mesmo com trial expirado
    private const ALLOWED_WRITE_PREFIXES = [
        'settings.',
        'subscription.',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || $user->tenant->isActive()) {
            return $next($request);
        }

        // Trial expirado — leituras sempre liberadas
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        // Rotas de configuração e assinatura sempre liberadas
        $routeName = $request->route()?->getName() ?? '';
        foreach (self::ALLOWED_WRITE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        // Bloqueia escrita
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'subscription_inactive',
                'message' => 'Seu trial expirou. Assine um plano para continuar usando.',
            ], 402);
        }

        return redirect()->back()->with('error', 'Seu trial expirou. Assine um plano para continuar usando.');
    }
}
