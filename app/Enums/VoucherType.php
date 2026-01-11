<?php

namespace App\Enums;

enum VoucherType: string
{
    case RECEIPT = 'receipt';
    case PAYMENT = 'payment';
    
    public function label(): string
    {
        return match($this) {
            self::RECEIPT => __('vouchers.types.receipt'),
            self::PAYMENT => __('vouchers.types.payment'),
        };
    }
}
