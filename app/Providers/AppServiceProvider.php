<?php

namespace App\Providers;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Observers\PaymentObserver;
use App\Services\SystemSettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set locale early - check session, cookie, or use default
        $locale = null;
        
        // Try to get locale from session if available
        if (request()->hasSession()) {
            $locale = session('locale');
        }
        
        // Try to get locale from cookie as fallback
        if (!$locale && request()->hasCookie('locale')) {
            $locale = request()->cookie('locale');
        }
        
        // Use default locale if none found
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }
        
        // Ensure locale is valid
        if (!in_array($locale, ['en', 'ar'])) {
            $locale = config('app.locale', 'en');
        }
        
        // Set the application locale
        app()->setLocale($locale);
        
        // Set Carbon locale for date formatting
        if (class_exists(\Carbon\Carbon::class)) {
            \Carbon\Carbon::setLocale($locale);
        }
        
        // Ensure system settings exist on app boot (fail-safe)
        app(SystemSettingsService::class)->ensureSystemSettingsExist();

        // Register observers
        Payment::observe(PaymentObserver::class);
        
        // Helper function to sanitize UTF-8 strings
        if (!function_exists('sanitize_utf8')) {
            function sanitize_utf8($value) {
                if (is_string($value)) {
                    // Remove invalid UTF-8 characters
                    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
                
                if (is_array($value)) {
                    return array_map('sanitize_utf8', $value);
                }
                
                if (is_object($value)) {
                    foreach (get_object_vars($value) as $key => $val) {
                        $value->$key = sanitize_utf8($val);
                    }
                }
                
                return $value;
            }
        }
    }
}
