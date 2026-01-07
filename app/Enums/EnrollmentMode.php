<?php

namespace App\Enums;

enum EnrollmentMode: string
{
    case COURSE_FULL = 'course_full';
    case PER_SESSION = 'per_session';
    case TRIAL = 'trial';
}

