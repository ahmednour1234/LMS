<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/admin/locale/toggle', function () {
    $currentLocale = session('locale', app()->getLocale());
    $newLocale = $currentLocale === 'ar' ? 'en' : 'ar';

    // Ensure locale is valid
    if (!in_array($newLocale, ['en', 'ar'])) {
        $newLocale = 'en';
    }

    // Save to session
    Session::put('locale', $newLocale);

    // Set application locale
    app()->setLocale($newLocale);

    // Set Carbon locale for date formatting
    if (class_exists(\Carbon\Carbon::class)) {
        \Carbon\Carbon::setLocale($newLocale);
    }

    $redirect = request()->input('redirect', url()->previous());
    if (empty($redirect) || !str_starts_with($redirect, url('/'))) {
        // Use the Filament admin panel URL directly
        $redirect = url('/admin');
    }

    return redirect()->to($redirect);
})->name('filament.admin.locale.toggle')->middleware(['web', 'auth']);

// Public routes (no authentication required)
Route::middleware(['web'])->group(function () {
    Route::get('/enrollment/{reference}', [App\Http\Controllers\PublicEnrollmentController::class, 'show'])
        ->name('public.enrollment.show');
    Route::get('/invoice/{id}', [App\Http\Controllers\PublicInvoiceController::class, 'show'])
        ->name('public.invoice.show');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/exports/excel', [App\Http\Controllers\ExportController::class, 'excel'])
        ->name('filament.admin.exports.excel');
    Route::get('/admin/exports/pdf', [App\Http\Controllers\ExportController::class, 'pdf'])
        ->name('filament.admin.exports.pdf');
    Route::get('/admin/exports/print', [App\Http\Controllers\ExportController::class, 'print'])
        ->name('filament.admin.exports.print');
    Route::get('/admin/journals/{journal}/print', [App\Http\Controllers\JournalController::class, 'print'])
        ->name('journals.print');
    Route::get('/admin/enrollments/{enrollment}/print', [App\Http\Controllers\EnrollmentController::class, 'print'])
        ->name('enrollments.print');
    Route::get('/admin/invoices/{invoice}/print', [App\Http\Controllers\InvoiceController::class, 'print'])
        ->name('invoices.print');
});
