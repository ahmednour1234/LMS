<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from session or default to config
        $locale = session('locale', config('app.locale', 'en'));
        
        // Ensure locale is valid (fallback to 'en' if invalid)
        if (!in_array($locale, ['en', 'ar'])) {
            $locale = config('app.locale', 'en');
            session(['locale' => $locale]);
        }
        
        // Set the application locale
        app()->setLocale($locale);
        
        // Set locale for Carbon (date formatting)
        if (class_exists(\Carbon\Carbon::class)) {
            \Carbon\Carbon::setLocale($locale);
        }
        
        return $next($request);
    }
}

