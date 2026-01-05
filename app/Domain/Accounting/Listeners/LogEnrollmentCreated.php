<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Enrollment\Events\EnrollmentCreated;
use App\Services\AuditLogger;

class LogEnrollmentCreated
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {
    }

    public function handle(EnrollmentCreated $event): void
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
        ];

        $this->auditLogger->log(
            action: 'enrollment_created',
            subject: $enrollment,
            meta: $meta,
            branchId: $enrollment->branch_id,
            userId: $enrollment->user_id
        );
    }
}

