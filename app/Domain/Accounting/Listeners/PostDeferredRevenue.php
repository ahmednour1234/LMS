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
            ->first();

        if ($existingJournal) {
            Log::info('Deferred revenue journal already exists for enrollment', [
                'enrollment_id' => $enrollment->id,
                'journal_id' => $existingJournal->id,
            ]);
            return;
        }

        // Note: This listener may not post anything if payment not yet received
        // Deferred revenue is typically posted when payment is received (PaymentPaid event)
        // This listener is kept for potential future use or if enrollment includes payment amount
    }
}

