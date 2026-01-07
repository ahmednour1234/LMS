<?php

namespace App\Domain\Training\Enums;

enum DeliveryType: string
{
    case Onsite = 'onsite';
    case Online = 'online';
    case Hybrid = 'hybrid';
}

