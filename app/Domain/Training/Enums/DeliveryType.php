<?php

namespace App\Domain\Training\Enums;

enum DeliveryType: string
{
    case Onsite = 'onsite';
    case Online = 'online';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';
}

