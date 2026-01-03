<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\RefundCreated;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Services\AccountingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PostRefundEntry implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AccountingService $accountingService
    ) {
    }

    public function handle(RefundCreated $event): void
    {
        $refund = $event->refund;

        // Idempotency check: skip if journal already exists
        $existingJournal = Journal::where('reference_type', 'refund')
            ->where('reference_id', $refund->id)
            ->first();

        if ($existingJournal) {
            Log::info('Refund journal already exists', [
                'refund_id' => $refund->id,
                'journal_id' => $existingJournal->id,
            ]);
            return;
        }

        // Get account code from payment method config or default
        $accountCode = $refund->paymentMethod?->config['account_code'] ?? '1110'; // Default cash account

        $this->accountingService->postRefund(
            accountCode: $accountCode,
            amount: (float) $refund->amount,
            referenceType: 'refund',
            referenceId: $refund->id,
            branchId: $refund->branch_id,
            description: "Refund issued: {$refund->reference}",
            user: $refund->creator
        );
    }
}

