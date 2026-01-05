<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\ArInstallment;
use App\Services\PdfService;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Print installment
     */
    public function print(ArInstallment $installment)
    {
        $installment->load(['arInvoice.enrollment.student', 'arInvoice.enrollment.course', 'arInvoice.branch', 'arInvoice.arInstallments']);
        
        return $this->pdfService->render('pdf.installment', [
            'installment' => $installment,
        ]);
    }
}

