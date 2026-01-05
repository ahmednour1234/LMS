<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\PaymentPaid;
use App\Services\AuditLogger;

class LogPaymentPaid
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {
    }

    public function handle(PaymentPaid $event): void
    {
        $payment = $event->payment;
        
        // Load relationships if needed
        $payment->loadMissing(['installment.arInvoice']);

        $meta = [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'method' => $payment->method,
            'gateway_ref' => $payment->gateway_ref,
            'enrollment_id' => $payment->enrollment_id,
            'installment_id' => $payment->installment_id,
            'invoice_no' => $payment->installment?->arInvoice?->id ?? null,
        ];

        $this->auditLogger->log(
            action: 'payment_paid',
            subject: $payment,
            meta: $meta,
            branchId: $payment->branch_id,
            userId: $payment->user_id
        );
    }
}

