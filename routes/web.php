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
    Route::get('/payment/{id}', [App\Http\Controllers\PublicPaymentController::class, 'show'])
        ->name('public.payment.show');
    Route::get('/installment/{id}', [App\Http\Controllers\PublicInstallmentController::class, 'show'])
        ->name('public.installment.show');
});

// Teacher authentication routes
Route::prefix('teacher')->name('teacher.')->middleware(['web'])->group(function () {
    // Public routes
    Route::post('/register', App\Http\Controllers\Teacher\Auth\RegisterController::class)
        ->name('register');
    
    Route::post('/login', App\Http\Controllers\Teacher\Auth\LoginController::class)
        ->name('login')
        ->middleware('throttle:5,1');
    
    Route::post('/forgot-password', App\Http\Controllers\Teacher\Auth\ForgotPasswordController::class)
        ->name('forgot-password')
        ->middleware('throttle:3,1');
    
    Route::post('/reset-password', App\Http\Controllers\Teacher\Auth\ResetPasswordController::class)
        ->name('reset-password');
    
    // Protected route
    Route::post('/logout', App\Http\Controllers\Teacher\Auth\LogoutController::class)
        ->name('logout')
        ->middleware('auth:teacher');
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
    Route::get('/admin/receipt-vouchers/{voucher}/print', [App\Http\Controllers\VoucherController::class, 'printReceipt'])
        ->name('filament.admin.resources.receipt-vouchers.print');
    Route::get('/admin/payment-vouchers/{voucher}/print', [App\Http\Controllers\VoucherController::class, 'printPayment'])
        ->name('filament.admin.resources.payment-vouchers.print');
    Route::get('/admin/enrollments/{enrollment}/print', [App\Http\Controllers\EnrollmentController::class, 'print'])
        ->name('enrollments.print');
    Route::get('/admin/invoices/{invoice}/print', [App\Http\Controllers\InvoiceController::class, 'print'])
        ->name('invoices.print');
    Route::get('/admin/payments/{payment}/print', [App\Http\Controllers\PaymentController::class, 'print'])
        ->name('payments.print');
    Route::get('/admin/installments/{installment}/print', [App\Http\Controllers\InstallmentController::class, 'print'])
        ->name('installments.print');
});
