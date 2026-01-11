<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::DRAFT => __('vouchers.status.draft'),
            self::POSTED => __('vouchers.status.posted'),
            self::CANCELLED => __('vouchers.status.cancelled'),
        };
    }
}
