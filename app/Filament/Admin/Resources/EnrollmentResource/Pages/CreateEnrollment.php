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

        // Validate inputs
        if (empty($data['course_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'Course is required.',
            ]);
        }

        $registrationType = $data['registration_type'] ?? 'online';
        if (!in_array($registrationType, ['onsite', 'online'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'registration_type' => 'Registration type must be either "onsite" or "online".',
            ]);
        }

        // Validate branch_id required when registration_type is onsite
        if ($registrationType === 'onsite' && empty($data['branch_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => 'Branch is required for onsite enrollment.',
            ]);
        }

        // Resolve price using PricingService and override total_amount
        $pricingService = app(\App\Services\PricingService::class);
        $branchId = $data['branch_id'] ?? null;
        $coursePrice = $pricingService->resolveCoursePrice(
            $data['course_id'],
            $branchId,
            $registrationType
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

        // Set created_by
        $data['created_by'] = auth()->id();

        return $data;
    }
}
