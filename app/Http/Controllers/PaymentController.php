<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\Payment;
use App\Services\PdfService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Print payment
     */
    public function print(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.course', 'branch', 'user', 'installment']);
        
        return $this->pdfService->render('pdf.payment', [
            'payment' => $payment,
        ]);
    }
}

