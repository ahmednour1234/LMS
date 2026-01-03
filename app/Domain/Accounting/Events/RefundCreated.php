<?php

namespace App\Domain\Accounting\Events;

use App\Domain\Accounting\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $refund
    ) {
    }
}

