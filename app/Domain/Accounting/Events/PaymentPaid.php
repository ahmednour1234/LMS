<?php

namespace App\Domain\Accounting\Events;

use App\Domain\Accounting\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment
    ) {
    }
}

