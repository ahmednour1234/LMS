<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\PaymentPaid;
use App\Domain\Accounting\Models\ArInvoice;
use Illuminate\Support\Facades\Log;

class UpdateArInvoiceStatus
{
    /**
     * Handle the event - update AR invoice status immediately when payment is recorded
     */
    public function handle(PaymentPaid $event): void
    {
        $payment = $event->payment;

        // Find AR invoice ID from payment's installment or enrollment
        $arInvoiceId = null;
        if ($payment->installment_id) {
            $payment->loadMissing('installment.arInvoice');
            $arInvoiceId = $payment->installment?->ar_invoice_id;
        } elseif ($payment->enrollment_id) {
            $payment->loadMissing('enrollment.arInvoice');
            $arInvoiceId = $payment->enrollment?->arInvoice?->id;
        }

        // Update AR invoice status immediately if invoice exists
        if ($arInvoiceId) {
            $invoice = ArInvoice::find($arInvoiceId);
            if ($invoice) {
                $invoice->updateStatus();
                Log::info('AR invoice status updated immediately after payment', [
                    'payment_id' => $payment->id,
                    'ar_invoice_id' => $arInvoiceId,
                    'status' => $invoice->status,
                ]);
            }
        }
    }
}

