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
        // Validate branch_id required when registration_type is onsite
        if (($data['registration_type'] ?? 'online') === 'onsite' && empty($data['branch_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => __('validation.required', ['attribute' => __('enrollments.branch')]) ?? 'Branch is required for onsite registration.',
            ]);
        }

        // Resolve price using PricingService and override total_amount
        if (!empty($data['course_id'])) {
            $registrationType = $data['registration_type'] ?? 'online';
            $branchId = $data['branch_id'] ?? null;
            $pricingType = $data['pricing_type'] ?? 'full';

            $pricingService = app(\App\Services\PricingService::class);
            $coursePrice = $pricingService->resolveCoursePrice(
                $data['course_id'],
                $branchId,
                $registrationType
            );

            if (!$coursePrice) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'course_id' => __('enrollments.no_price_found_error') ?? 'No active price found for this course, branch, and registration type combination. Please configure pricing.',
                ]);
            }

            // Override total_amount from resolved price (ignore client input)
            $data['total_amount'] = (float) $coursePrice->price;

            // Validate installment constraints if pricing_type is installment
            if ($pricingType === 'installment') {
                if (!$coursePrice->allow_installments) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'pricing_type' => __('enrollments.installment_not_allowed') ?? 'Installment pricing is not allowed for this course/registration type combination.',
                    ]);
                }

                // Note: Additional validation for down_payment and installments_count
                // would go here if those fields exist in the enrollment form
                // For Phase 1, we just validate that installments are allowed
            }
        }

        // Set updated_by
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
