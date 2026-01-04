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

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/exports/excel', [App\Http\Controllers\ExportController::class, 'excel'])
        ->name('filament.admin.exports.excel');
    Route::get('/admin/exports/pdf', [App\Http\Controllers\ExportController::class, 'pdf'])
        ->name('filament.admin.exports.pdf');
    Route::get('/admin/exports/print', [App\Http\Controllers\ExportController::class, 'print'])
        ->name('filament.admin.exports.print');
});
