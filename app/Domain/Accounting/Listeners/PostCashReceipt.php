<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\PaymentPaid;
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

        $this->accountingService->postPayment(
            accountCode: $accountCode,
            amount: (float) $payment->amount,
            referenceType: 'payment',
            referenceId: $payment->id,
            branchId: $payment->branch_id,
            description: "Payment received: {$payment->reference}",
            user: $payment->creator
        );
    }
}

