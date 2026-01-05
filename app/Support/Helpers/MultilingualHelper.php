<?php

namespace App\Support\Helpers;

class MultilingualHelper
{
    /**
     * Get the current locale from session, cookie, or app config
     */
    public static function getCurrentLocale(): string
    {
        // Try session first
        if (request()->hasSession() && session()->has('locale')) {
            $locale = session('locale');
            if (in_array($locale, ['en', 'ar'])) {
                return $locale;
            }
        }
        
        // Try cookie as fallback
        if (request()->hasCookie('locale')) {
            $locale = request()->cookie('locale');
            if (in_array($locale, ['en', 'ar'])) {
                return $locale;
            }
        }
        
        // Use app locale
        $locale = app()->getLocale();
        if (in_array($locale, ['en', 'ar'])) {
            return $locale;
        }
        
        // Final fallback
        return config('app.locale', 'en');
    }

    /**
     * Format multilingual array field (name, description, etc.)
     * Returns the value for the current locale, with fallbacks
     */
    public static function formatMultilingualField($state, ?string $fallbackLocale = null): string
    {
        if (empty($state)) {
            return '';
        }
        
        // If it's not an array, return as string
        if (!is_array($state)) {
            return (string) $state;
        }
        
        $locale = self::getCurrentLocale();
        $fallback = $fallbackLocale ?? ($locale === 'ar' ? 'en' : 'ar');
        
        // Try current locale first
        if (isset($state[$locale]) && !empty($state[$locale])) {
            return (string) $state[$locale];
        }
        
        // Try fallback locale
        if (isset($state[$fallback]) && !empty($state[$fallback])) {
            return (string) $state[$fallback];
        }
        
        // Try the other locale
        $otherLocale = $fallback === 'ar' ? 'en' : 'ar';
        if (isset($state[$otherLocale]) && !empty($state[$otherLocale])) {
            return (string) $state[$otherLocale];
        }
        
        // Return first available value
        if (!empty($state)) {
            return (string) reset($state);
        }
        
        return '';
    }
}

