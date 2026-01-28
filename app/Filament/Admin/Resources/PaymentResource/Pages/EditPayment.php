<?php

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Domain\Enrollment\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $payment = $this->record;
        $enrollment = $payment->enrollment;

        if (!$enrollment) {
            return;
        }

        // Calculate total paid amount from all paid payments
        $totalPaid = $enrollment->payments()
            ->where('status', 'paid')
            ->sum('amount');

        // Update paid_amount in enrollment
        $enrollment->paid_amount = $totalPaid;

        $totalAmount = $enrollment->total_amount ?? 0;

        if ($payment->status === 'paid') {
            if ($enrollment->status === EnrollmentStatus::PENDING_PAYMENT) {
                $enrollment->status = EnrollmentStatus::ACTIVE;
                $enrollment->enrolled_at = $enrollment->enrolled_at ?? now();
            } elseif ($totalPaid >= $totalAmount && $enrollment->status !== EnrollmentStatus::ACTIVE) {
                $enrollment->status = EnrollmentStatus::ACTIVE;
                $enrollment->enrolled_at = $enrollment->enrolled_at ?? now();
            }
        } else {
            if ($totalPaid < $totalAmount && $enrollment->status === EnrollmentStatus::ACTIVE) {
                $enrollment->status = EnrollmentStatus::PENDING_PAYMENT;
            }
        }

        $enrollment->save();
    }
}
