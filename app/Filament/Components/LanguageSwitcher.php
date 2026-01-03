<?php

namespace App\Filament\Components;

use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public function toggleLanguage(): void
    {
        $currentLocale = app()->getLocale();
        $newLocale = $currentLocale === 'ar' ? 'en' : 'ar';
        
        Session::put('locale', $newLocale);
        app()->setLocale($newLocale);
        
        $this->redirect(url()->current(), navigate: true);
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

