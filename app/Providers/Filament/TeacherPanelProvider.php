<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TeacherPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Ensure locale is set early for Filament resource discovery
        // This runs before Filament discovers resources, so translations work correctly
        $locale = null;
        
        // Try session first
        if (request()->hasSession()) {
            $locale = session('locale');
        }
        
        // Try cookie as fallback
        if (!$locale && request()->hasCookie('locale')) {
            $locale = request()->cookie('locale');
        }
        
        // Use default if none found
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }
        
        // Ensure locale is valid
        if (!in_array($locale, ['en', 'ar'])) {
            $locale = config('app.locale', 'en');
        }
        
        // Set the application locale
        app()->setLocale($locale);
        
        // Set Carbon locale
        if (class_exists(\Carbon\Carbon::class)) {
            \Carbon\Carbon::setLocale($locale);
        }
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('teacher')
            ->path('teacher-admin')
            ->login(\App\Filament\Teacher\Pages\Auth\Login::class)
            ->registration(\App\Filament\Teacher\Pages\Auth\Register::class)
            ->passwordReset(
                \App\Filament\Teacher\Pages\Auth\RequestPasswordReset::class,
                \App\Filament\Teacher\Pages\Auth\ResetPassword::class
            )
            ->authGuard('teacher')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Teacher/Resources'), for: 'App\\Filament\\Teacher\\Resources')
            ->discoverPages(in: app_path('Filament/Teacher/Pages'), for: 'App\\Filament\\Teacher\\Pages')
            ->discoverWidgets(in: app_path('Filament/Teacher/Widgets'), for: 'App\\Filament\\Teacher\\Widgets')
            ->pages([
                \App\Filament\Teacher\Pages\Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ], isPersistent: true)
            ->renderHook(
                'panels::topbar.end',
                fn () => view('filament.components.language-switcher-wrapper')
            )
            ->renderHook(
                'panels::body.start',
                fn () => view('filament.components.rtl-directive')
            );
    }
}
