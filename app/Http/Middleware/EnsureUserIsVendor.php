<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isVendor()) {
            return response()->json([
                'message' => 'Accès refusé. Compte vendeur requis.',
            ], 403);
        }

        return $next($request);
    }
}
