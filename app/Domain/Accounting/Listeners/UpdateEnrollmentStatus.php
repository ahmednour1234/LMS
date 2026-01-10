<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\PaymentPaid;
use App\Domain\Enrollment\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use Illuminate\Support\Facades\Log;

class UpdateEnrollmentStatus
{
    public function handle(PaymentPaid $event): void
    {
        $payment = $event->payment;

        if (!$payment->enrollment_id) {
            return;
        }

        $enrollment = Enrollment::find($payment->enrollment_id);
        if (!$enrollment) {
            return;
        }

        $totalPaid = $enrollment->payments()
            ->where('status', 'paid')
            ->sum('amount');

        $totalAmount = $enrollment->total_amount ?? 0;
        $currentStatus = $enrollment->status;

        if ($currentStatus === EnrollmentStatus::COMPLETED || $currentStatus === EnrollmentStatus::CANCELLED) {
            return;
        }

        $newStatus = null;

        if ($totalPaid >= $totalAmount && $totalAmount > 0) {
            $newStatus = EnrollmentStatus::ACTIVE;
        } elseif ($totalPaid > 0 && $totalPaid < $totalAmount) {
            $newStatus = EnrollmentStatus::PENDING_PAYMENT;
        } elseif ($totalPaid == 0) {
            $newStatus = EnrollmentStatus::PENDING;
        }

        if ($newStatus && $currentStatus !== $newStatus) {
            $updateData = ['status' => $newStatus];

            if ($newStatus === EnrollmentStatus::ACTIVE && !$enrollment->started_at) {
                $updateData['started_at'] = now();
            }

            $enrollment->update($updateData);

            Log::info('Enrollment status updated after payment', [
                'enrollment_id' => $enrollment->id,
                'payment_id' => $payment->id,
                'total_paid' => $totalPaid,
                'total_amount' => $totalAmount,
                'old_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
            ]);
        }
    }
}

