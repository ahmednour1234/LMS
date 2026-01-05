<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\Payment;
use Illuminate\Http\Request;

class PublicPaymentController extends Controller
{
    /**
     * Show public payment view (for QR code link)
     */
    public function show(int $id)
    {
        $payment = Payment::with(['enrollment.student', 'enrollment.course', 'branch', 'user'])
            ->findOrFail($id);

        return view('public.payment', [
            'payment' => $payment,
        ]);
    }
}

