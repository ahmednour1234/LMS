<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\Voucher;
use App\Services\PdfService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    public function printReceipt(Voucher $voucher)
    {
        $voucher->load(['cashBankAccount', 'counterpartyAccount', 'costCenter', 'branch', 'creator', 'approver']);
        return $this->pdfService->render('pdf.voucher', [
            'voucher' => $voucher,
        ]);
    }

    public function printPayment(Voucher $voucher)
    {
        $voucher->load(['cashBankAccount', 'counterpartyAccount', 'costCenter', 'branch', 'creator', 'approver']);
        return $this->pdfService->render('pdf.voucher', [
            'voucher' => $voucher,
        ]);
    }
}
