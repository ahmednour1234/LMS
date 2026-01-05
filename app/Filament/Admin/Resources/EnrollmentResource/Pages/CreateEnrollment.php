<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\Pages;

use App\Filament\Admin\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Reference will be auto-generated in the model boot method
        // Set enrolled_at if not provided
        if (empty($data['enrolled_at'])) {
            $data['enrolled_at'] = now();
        }

        // Set branch_id from user if not provided and user is not super admin
        if (empty($data['branch_id']) && !auth()->user()->isSuperAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        // Set registration_type based on course if not set (for non-hybrid courses)
        if (!empty($data['course_id']) && empty($data['registration_type'])) {
            $course = \App\Domain\Training\Models\Course::find($data['course_id']);
            if ($course) {
                $data['registration_type'] = match ($course->delivery_type) {
                    \App\Domain\Training\Enums\DeliveryType::Onsite => 'onsite',
                    \App\Domain\Training\Enums\DeliveryType::Online => 'online',
                    \App\Domain\Training\Enums\DeliveryType::Virtual => 'online',
                    \App\Domain\Training\Enums\DeliveryType::Hybrid => $data['registration_type'] ?? 'online',
                    default => 'online',
                };
            }
        }

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

        // Set created_by
        $data['created_by'] = auth()->id();

        return $data;
    }
}
