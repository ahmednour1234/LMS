<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/admin/locale/toggle', function () {
    $currentLocale = app()->getLocale();
    $newLocale = $currentLocale === 'ar' ? 'en' : 'ar';
    
    Session::put('locale', $newLocale);
    app()->setLocale($newLocale);
    
    $redirect = request()->input('redirect', url()->previous());
    if (empty($redirect) || !str_starts_with($redirect, url('/'))) {
        $redirect = route('filament.admin.pages.dashboard');
    }
    
    return redirect()->to($redirect);
})->name('filament.admin.locale.toggle')->middleware(['web', 'auth']);
