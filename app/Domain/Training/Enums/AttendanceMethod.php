<?php

namespace App\Domain\Training\Enums;

enum AttendanceMethod: string
{
    case MANUAL = 'manual';
    case QR = 'qr';
    case STUDENT_CHECKIN = 'student_checkin';
}
