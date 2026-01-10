<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\Pages;

use App\Filament\Admin\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate inputs
        if (empty($data['course_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'Course is required.',
            ]);
        }

        $deliveryType = $data['delivery_type'] ?? 'online';
        if (!in_array($deliveryType, ['onsite', 'online'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'delivery_type' => 'Delivery type must be either "onsite" or "online".',
            ]);
        }

        // Validate branch_id required when delivery_type is onsite
        if ($deliveryType === 'onsite' && empty($data['branch_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => 'Branch is required for onsite enrollment.',
            ]);
        } else {
            // For online, ensure branch_id is null
            $data['branch_id'] = null;
        }

        // Resolve price using PricingService and override total_amount
        $pricingService = app(\App\Services\PricingService::class);
        $branchId = $data['branch_id'] ?? null;
        $coursePrice = $pricingService->resolveCoursePrice(
            $data['course_id'],
            $branchId,
            $deliveryType
        );

        if (!$coursePrice) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'No active course price found for this course/branch/delivery type combination.',
            ]);
        }

        // Override total_amount from resolved price (ignore client input)
        $data['total_amount'] = (float) $coursePrice->price;

        // Validate installment constraints if pricing_type is installment
        $pricingType = $data['pricing_type'] ?? 'full';
        if ($pricingType === 'installment') {
            if (!$coursePrice->allow_installments) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pricing_type' => 'Installment pricing is not allowed for this course/registration type combination.',
                ]);
            }

            // Additional validation for down_payment and installments_count would go here
            // if those fields exist in the enrollment form
        }

        // Set updated_by
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
