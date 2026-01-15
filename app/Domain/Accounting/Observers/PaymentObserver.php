<?php

namespace App\Domain\Accounting\Observers;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\JournalService;

class PaymentObserver
{
    public function __construct(
        protected JournalService $journalService
    ) {
    }

    public function created(Payment $payment): void
    {
        if ($payment->status === 'paid') {
            try {
                $this->journalService->createForPayment($payment);
            } catch (\Exception $e) {
                logger()->error('Failed to create journal for payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('status') && $payment->status === 'paid') {
            if (!$payment->journal()->exists()) {
                try {
                    $this->journalService->createForPayment($payment);
                } catch (\Exception $e) {
                    logger()->error('Failed to create journal for payment on status update', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }
    }
}
