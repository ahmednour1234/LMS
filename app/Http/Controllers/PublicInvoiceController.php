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

        return view('public.invoice', [
            'invoice' => $invoice,
        ]);
    }
}

