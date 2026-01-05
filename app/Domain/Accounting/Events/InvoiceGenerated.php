<?php

namespace App\Domain\Accounting\Events;

use App\Domain\Accounting\Models\ArInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ArInvoice $invoice
    ) {
    }
}

