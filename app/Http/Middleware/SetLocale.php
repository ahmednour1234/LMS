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
        $locale = null;

        // For API requests, check Accept-Language header first
        if ($request->is('api/*')) {
            $acceptLanguage = $request->header('Accept-Language');
            if ($acceptLanguage) {
                // Parse Accept-Language header (e.g., "en-US,en;q=0.9,ar;q=0.8")
                $locales = $this->parseAcceptLanguage($acceptLanguage);
                foreach ($locales as $lang) {
                    // Extract base language (e.g., "en" from "en-US")
                    $baseLang = explode('-', $lang)[0];
                    if (in_array($baseLang, ['en', 'ar'])) {
                        $locale = $baseLang;
                        break;
                    }
                }
            }
        }

        // For web requests or if no Accept-Language header, use session
        if (!$locale) {
            $locale = session('locale', config('app.locale', 'en'));
        }
        
        // Ensure locale is valid (fallback to 'en' if invalid)
        if (!in_array($locale, ['en', 'ar'])) {
            $locale = config('app.locale', 'en');
        }

        // Set session for web requests
        if (!$request->is('api/*') && $request->hasSession()) {
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

    /**
     * Parse Accept-Language header and return ordered list of languages.
     *
     * @param string $acceptLanguage
     * @return array
     */
    private function parseAcceptLanguage(string $acceptLanguage): array
    {
        $languages = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            if (str_contains($part, ';')) {
                [$lang, $q] = explode(';', $part, 2);
                $q = (float) str_replace('q=', '', trim($q));
                $languages[trim($lang)] = $q;
            } else {
                $languages[trim($part)] = 1.0;
            }
        }

        // Sort by quality value (descending)
        arsort($languages);

        return array_keys($languages);
    }
}

