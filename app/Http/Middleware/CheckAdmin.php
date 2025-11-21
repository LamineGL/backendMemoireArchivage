<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Non authentifié.'
            ], 401);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Accès refusé. Réservé aux administrateurs.'
            ], 403);
        }

        return $next($request);
    }
}
