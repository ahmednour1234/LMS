<?php

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Filament\Admin\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If user_id is not set, get it from enrollment
        if (empty($data['user_id']) && !empty($data['enrollment_id'])) {
            $enrollment = \App\Domain\Enrollment\Models\Enrollment::with('student')->find($data['enrollment_id']);
            if ($enrollment) {
                $data['user_id'] = $enrollment->user_id 
                    ?? $enrollment->student->user_id 
                    ?? auth()->id();
            }
        }

        // If still no user_id, use current user
        if (empty($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }

        // Set branch_id from enrollment if not set
        if (empty($data['branch_id']) && !empty($data['enrollment_id'])) {
            $enrollment = \App\Domain\Enrollment\Models\Enrollment::find($data['enrollment_id']);
            if ($enrollment && $enrollment->branch_id) {
                $data['branch_id'] = $enrollment->branch_id;
            } else {
                $data['branch_id'] = auth()->user()->branch_id;
            }
        }

        // Set created_by
        $data['created_by'] = auth()->id();

        return $data;
    }
}
