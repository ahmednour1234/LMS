<?php

namespace App\Domain\Enrollment\Events;

use App\Domain\Enrollment\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnrollmentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Enrollment $enrollment
    ) {
    }
}

