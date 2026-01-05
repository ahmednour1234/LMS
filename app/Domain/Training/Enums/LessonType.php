<?php

namespace App\Domain\Training\Enums;

enum LessonType: string
{
    case RECORDED = 'recorded';
    case LIVE = 'live';
    case MIXED = 'mixed';
}

