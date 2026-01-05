<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Services\AccountingService;
use App\Domain\Enrollment\Events\EnrollmentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PostDeferredRevenue implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AccountingService $accountingService
    ) {
    }

    public function handle(EnrollmentCreated $event): void
    {
        $enrollment = $event->enrollment;

        // Idempotency check: skip if journal already exists
        $existingJournal = Journal::where('reference_type', 'enrollment')
            ->where('reference_id', $enrollment->id)
            ->whereHas('journalLines', function ($query) {
                $query->whereHas('account', function ($q) {
                    $q->where('code', '1130'); // Accounts Receivable
                })->where('debit', '>', 0);
            })
            ->first();

        if ($existingJournal) {
            Log::info('Enrollment creation journal already exists', [
                'enrollment_id' => $enrollment->id,
                'journal_id' => $existingJournal->id,
            ]);
            return;
        }

        // Post journal: Dr AR (1130) / Cr Deferred Revenue (2130)
        $this->accountingService->postEnrollmentCreated(
            amount: (float) $enrollment->total_amount,
            referenceType: 'enrollment',
            referenceId: $enrollment->id,
            branchId: $enrollment->branch_id,
            description: "Enrollment created: {$enrollment->reference}",
            user: $enrollment->creator
        );

        Log::info('Enrollment creation journal posted', [
            'enrollment_id' => $enrollment->id,
            'amount' => $enrollment->total_amount,
        ]);
    }
}

