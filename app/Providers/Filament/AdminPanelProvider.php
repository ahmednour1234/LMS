<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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

class AdminPanelProvider extends PanelProvider
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
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('system')
                    ->label(__('navigation.groups.system')),
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
            ])
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
