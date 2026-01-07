<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\ArInvoice;
use App\Models\Setting;
use App\Services\PdfService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Print invoice
     */
    public function print(ArInvoice $invoice)
    {
        $invoice->load(['enrollment.student', 'enrollment.course', 'arInstallments', 'branch']);
        
        // Set locale to Arabic for invoice generation
        $originalLocale = app()->getLocale();
        app()->setLocale('ar');
        
        // Set Carbon locale for date formatting
        if (class_exists(\Carbon\Carbon::class)) {
            \Carbon\Carbon::setLocale('ar');
        }
        
        try {
            // Get required settings for invoice PDF
            $settings = [
                'app_name' => Setting::where('key', 'app_name')->first()?->value ?? [],
                'app_phone' => Setting::where('key', 'app_phone')->first()?->value ?? [],
                'app_whatsapp' => Setting::where('key', 'app_whatsapp')->first()?->value ?? [],
                'tax_registration_number' => Setting::where('key', 'tax_registration_number')->first()?->value ?? [],
                'commercial_registration_number' => Setting::where('key', 'commercial_registration_number')->first()?->value ?? [],
            ];
            
            return $this->pdfService->render('pdf.ar-invoice', [
                'invoice' => $invoice,
                'settings' => $settings,
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

