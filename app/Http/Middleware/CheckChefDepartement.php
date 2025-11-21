<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckChefDepartement
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

        $user = auth()->user();

        if (!$user->isAdmin() && !$user->isChef()) {
            return response()->json([
                'message' => 'Accès refusé. Réservé aux chefs de département et administrateurs.'
            ], 403);
        }

        return $next($request);
    }
}
