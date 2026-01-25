<?php

namespace App\Services\Student;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function createPayment(
        Student $student,
        int $enrollmentId,
        float $amount,
        string $paymentMethodId,
        ?string $gatewayReference = null,
        ?int $installmentId = null
    ): Payment {
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('id', $enrollmentId)
            ->firstOrFail();

        if ($amount <= 0) {
            throw new BusinessException('Payment amount must be greater than zero.');
        }

        $paidAmount = $this->getPaidAmount($enrollment);
        $dueAmount = max(0, ($enrollment->total_amount ?? 0) - $paidAmount);

        if ($amount > $dueAmount) {
            $amount = $dueAmount;
        }

        return DB::transaction(function () use (
            $enrollment,
            $amount,
            $paymentMethodId,
            $gatewayReference,
            $installmentId,
            $student
        ) {
            $payment = Payment::create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'amount' => $amount,
                'method' => $paymentMethodId,
                'gateway_ref' => $gatewayReference,
                'installment_id' => $installmentId,
                'status' => PaymentStatus::PENDING->value,
                'paid_at' => null,
                'branch_id' => $enrollment->branch_id,
            ]);

            return $payment;
        });
    }

    public function getPayments(Student $student, int $enrollmentId, int $perPage = 15)
    {
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('id', $enrollmentId)
            ->firstOrFail();

        return Payment::where('enrollment_id', $enrollment->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPaidAmount(Enrollment $enrollment): float
    {
        return (float) Payment::where('enrollment_id', $enrollment->id)
            ->where('status', PaymentStatus::COMPLETED->value)
            ->sum('amount');
    }

    public function getDueAmount(Enrollment $enrollment): float
    {
        $paidAmount = $this->getPaidAmount($enrollment);
        return max(0, ($enrollment->total_amount ?? 0) - $paidAmount);
    }

    public function getPaymentStatus(Enrollment $enrollment): string
    {
        $paidAmount = $this->getPaidAmount($enrollment);
        $totalAmount = (float) ($enrollment->total_amount ?? 0);

        if ($totalAmount <= 0) {
            return 'free';
        }

        if ($paidAmount >= $totalAmount - 0.01) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'unpaid';
    }
}
