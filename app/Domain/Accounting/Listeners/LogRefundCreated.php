<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\RefundCreated;
use App\Services\AuditLogger;

class LogRefundCreated
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {
    }

    public function handle(RefundCreated $event): void
    {
        $refund = $event->refund;

        $meta = [
            'refund_id' => $refund->id,
            'payment_id' => $refund->id, // Refund is a Payment model
            'amount' => $refund->amount,
            'method' => $refund->method,
            'enrollment_id' => $refund->enrollment_id,
            'installment_id' => $refund->installment_id,
            'status' => $refund->status,
        ];

        $this->auditLogger->log(
            action: 'refund_created',
            subject: $refund,
            meta: $meta,
            branchId: $refund->branch_id,
            userId: $refund->user_id
        );
    }
}

