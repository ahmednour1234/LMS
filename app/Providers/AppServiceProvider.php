<?php

namespace App\Providers;

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
