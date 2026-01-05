<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\ArInstallment;
use Illuminate\Http\Request;

class PublicInstallmentController extends Controller
{
    /**
     * Show public installment view (for QR code link)
     */
    public function show(int $id)
    {
        $installment = ArInstallment::with(['arInvoice.enrollment.student', 'arInvoice.enrollment.course', 'arInvoice.branch'])
            ->findOrFail($id);

        return view('public.installment', [
            'installment' => $installment,
        ]);
    }
}

