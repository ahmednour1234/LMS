<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\ArInvoice;
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
        
        return $this->pdfService->render('pdf.ar-invoice', [
            'invoice' => $invoice,
        ]);
    }
}

