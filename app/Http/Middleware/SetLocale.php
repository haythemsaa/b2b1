<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for locale in request header
        $locale = $request->header('Accept-Language');

        // Check for locale in query parameter
        if ($request->has('locale')) {
            $locale = $request->input('locale');
        }

        // Use authenticated user's preferred locale
        if ($request->user() && $request->user()->locale) {
            $locale = $request->user()->locale;
        }

        // Validate and set locale
        if ($locale && in_array($locale, ['fr', 'ar'])) {
            App::setLocale($locale);
        } else {
            App::setLocale('fr'); // Default to French
        }

        return $next($request);
    }
}
