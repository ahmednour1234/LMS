<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Services\AuditLogger;

class LogEnrollmentCompleted
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {
    }

    public function handle(EnrollmentCompleted $event): void
    {
        $enrollment = $event->enrollment;
        
        // Load relationships if needed
        $enrollment->loadMissing(['course', 'student']);

        $meta = [
            'enrollment_id' => $enrollment->id,
            'enrollment_reference' => $enrollment->reference,
            'total_amount' => $enrollment->total_amount,
            'course_id' => $enrollment->course_id,
            'course_name' => $enrollment->course?->name,
            'student_id' => $enrollment->student_id,
            'student_name' => $enrollment->student?->name,
            'completed_at' => $enrollment->completed_at?->toIso8601String(),
            'progress_percent' => $enrollment->progress_percent,
        ];

        $this->auditLogger->log(
            action: 'enrollment_completed',
            subject: $enrollment,
            meta: $meta,
            branchId: $enrollment->branch_id,
            userId: $enrollment->user_id
        );
    }
}

