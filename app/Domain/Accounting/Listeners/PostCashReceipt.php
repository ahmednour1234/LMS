<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\PaymentPaid;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Services\AccountingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PostCashReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AccountingService $accountingService
    ) {
    }

    public function handle(PaymentPaid $event): void
    {
        $payment = $event->payment;

        // Idempotency check: skip if journal already exists
        $existingJournal = Journal::where('reference_type', 'payment')
            ->where('reference_id', $payment->id)
            ->first();

        if ($existingJournal) {
            Log::info('Cash receipt journal already exists for payment', [
                'payment_id' => $payment->id,
                'journal_id' => $existingJournal->id,
            ]);
            return;
        }

        // Get account code from payment method config or default
        $accountCode = $payment->paymentMethod?->config['account_code'] ?? '1110'; // Default cash account

        // Find AR invoice ID from payment's installment or enrollment
        $arInvoiceId = null;
        if ($payment->installment_id) {
            $payment->loadMissing('installment.arInvoice');
            $arInvoiceId = $payment->installment?->ar_invoice_id;
        } elseif ($payment->enrollment_id) {
            $payment->loadMissing('enrollment.arInvoice');
            $arInvoiceId = $payment->enrollment?->arInvoice?->id;
        }

        $this->accountingService->postPayment(
            accountCode: $accountCode,
            amount: (float) $payment->amount,
            referenceType: 'payment',
            referenceId: $payment->id,
            branchId: $payment->branch_id,
            description: "Payment received: {$payment->reference}",
            user: $payment->creator,
            arInvoiceId: $arInvoiceId
        );

        // Update AR invoice status if invoice exists
        if ($arInvoiceId) {
            $invoice = ArInvoice::find($arInvoiceId);
            if ($invoice) {
                $invoice->updateStatus();
                Log::info('AR invoice status updated after payment', [
                    'payment_id' => $payment->id,
                    'ar_invoice_id' => $arInvoiceId,
                    'status' => $invoice->status,
                ]);
            }
        }
    }
}

