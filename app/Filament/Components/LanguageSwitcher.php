<?php

namespace App\Filament\Components;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component implements HasActions
{
    use InteractsWithActions;

    public function toggleLanguageAction(): Action
    {
        return Action::make('toggleLanguage')
            ->label(fn () => app()->getLocale() === 'ar' ? 'EN' : 'AR')
            ->icon('heroicon-o-language')
            ->action(function () {
                $currentLocale = app()->getLocale();
                $newLocale = $currentLocale === 'ar' ? 'en' : 'ar';
                
                Session::put('locale', $newLocale);
                app()->setLocale($newLocale);
                
                $redirectUrl = url()->current();
                $this->redirect($redirectUrl, navigate: true);
            });
    }

    public function render()
    {
        $currentLocale = app()->getLocale();
        $switchLabel = $currentLocale === 'ar' ? 'EN' : 'AR';
        
        return view('filament.components.language-switcher', [
            'switchLabel' => $switchLabel,
            'currentLocale' => $currentLocale,
        ]);
    }
}

