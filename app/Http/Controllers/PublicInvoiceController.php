<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\ArInvoice;
use Illuminate\Http\Request;

class PublicInvoiceController extends Controller
{
    /**
     * Show public invoice view
     */
    public function show(int $id)
    {
        $invoice = ArInvoice::with(['enrollment.student', 'enrollment.course', 'arInstallments', 'branch'])
            ->findOrFail($id);

        // Set locale to Arabic for invoice display
        $originalLocale = app()->getLocale();
        app()->setLocale('ar');
        
        // Set Carbon locale for date formatting
        if (class_exists(\Carbon\Carbon::class)) {
            \Carbon\Carbon::setLocale('ar');
        }

        try {
            return view('public.invoice', [
                'invoice' => $invoice,
            ]);
        } finally {
            // Restore original locale
            app()->setLocale($originalLocale);
            if (class_exists(\Carbon\Carbon::class)) {
                \Carbon\Carbon::setLocale($originalLocale);
            }
        }
    }
}

