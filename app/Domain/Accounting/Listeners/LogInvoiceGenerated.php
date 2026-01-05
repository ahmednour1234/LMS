<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\InvoiceGenerated;
use App\Services\AuditLogger;

class LogInvoiceGenerated
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {
    }

    public function handle(InvoiceGenerated $event): void
    {
        $invoice = $event->invoice;
        
        // Load enrollment relationship if needed
        $invoice->loadMissing('enrollment');

        $meta = [
            'invoice_id' => $invoice->id,
            'invoice_no' => $invoice->id, // Using ID as invoice number
            'enrollment_id' => $invoice->enrollment_id,
            'enrollment_reference' => $invoice->enrollment?->reference,
            'total_amount' => $invoice->total_amount,
            'due_amount' => $invoice->due_amount,
            'status' => $invoice->status,
            'issued_at' => $invoice->issued_at?->toIso8601String(),
        ];

        $this->auditLogger->log(
            action: 'invoice_generated',
            subject: $invoice,
            meta: $meta,
            branchId: $invoice->branch_id,
            userId: $invoice->user_id
        );
    }
}

