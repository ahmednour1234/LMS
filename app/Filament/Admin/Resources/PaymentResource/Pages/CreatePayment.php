<?php

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Domain\Enrollment\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Always ensure user_id is set - get it from enrollment or use current user
        if (empty($data['user_id']) || $data['user_id'] === null) {
            if (!empty($data['enrollment_id'])) {
                $enrollment = \App\Domain\Enrollment\Models\Enrollment::with('student')->find($data['enrollment_id']);
                if ($enrollment) {
                    $data['user_id'] = $enrollment->user_id 
                        ?? ($enrollment->student ? $enrollment->student->user_id : null)
                        ?? auth()->id();
                } else {
                    $data['user_id'] = auth()->id();
                }
            } else {
                $data['user_id'] = auth()->id();
            }
        }

        // Ensure branch_id is set
        if (empty($data['branch_id']) || $data['branch_id'] === null) {
            if (!empty($data['enrollment_id'])) {
                $enrollment = \App\Domain\Enrollment\Models\Enrollment::find($data['enrollment_id']);
                if ($enrollment && $enrollment->branch_id) {
                    $data['branch_id'] = $enrollment->branch_id;
                } else {
                    $data['branch_id'] = auth()->user()->branch_id;
                }
            } else {
                $data['branch_id'] = auth()->user()->branch_id;
            }
        }

        // Always set created_by
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
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
        $enrollment->save();

        $totalAmount = $enrollment->total_amount ?? 0;

        if ($payment->status === 'paid') {
            if ($enrollment->status === EnrollmentStatus::PENDING_PAYMENT) {
                $enrollment->status = EnrollmentStatus::ACTIVE;
                $enrollment->enrolled_at = $enrollment->enrolled_at ?? now();
                $enrollment->save();
            } elseif ($totalPaid >= $totalAmount && $enrollment->status !== EnrollmentStatus::ACTIVE) {
                $enrollment->status = EnrollmentStatus::ACTIVE;
                $enrollment->enrolled_at = $enrollment->enrolled_at ?? now();
                $enrollment->save();
            }
        }
    }
}
