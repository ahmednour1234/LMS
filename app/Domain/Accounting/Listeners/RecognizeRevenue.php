<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Services\AccountingService;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RecognizeRevenue implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AccountingService $accountingService
    ) {
    }

    public function handle(EnrollmentCompleted $event): void
    {
        $enrollment = $event->enrollment;

        // Idempotency check: skip if journal already exists with training revenue account (4110)
        $trainingRevenueAccount = Account::where('code', '4110')->where('is_active', true)->first();
        
        if ($trainingRevenueAccount) {
            $existingJournal = Journal::where('reference_type', 'enrollment')
                ->where('reference_id', $enrollment->id)
                ->whereHas('journalLines', function ($query) use ($trainingRevenueAccount) {
                    $query->where('account_id', $trainingRevenueAccount->id)
                        ->where('credit', '>', 0);
                })
                ->first();

            if ($existingJournal) {
                Log::info('Revenue recognition journal already exists for enrollment', [
                    'enrollment_id' => $enrollment->id,
                    'journal_id' => $existingJournal->id,
                ]);
                return;
            }
        }

        // Get amount from enrollment or related payment
        // For now, assuming enrollment has amount or we need to fetch from payment
        // This may need adjustment based on your data model
        $amount = 0; // TODO: Get amount from enrollment or payment relationship

        if ($amount <= 0) {
            Log::warning('Cannot recognize revenue: amount is zero or negative', [
                'enrollment_id' => $enrollment->id,
            ]);
            return;
        }

        $this->accountingService->postCourseCompletion(
            amount: $amount,
            referenceType: 'enrollment',
            referenceId: $enrollment->id,
            branchId: $enrollment->branch_id,
            description: "Course completion revenue recognition: {$enrollment->reference}",
            user: $enrollment->creator
        );
    }
}

