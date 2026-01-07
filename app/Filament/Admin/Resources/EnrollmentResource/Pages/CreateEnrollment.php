<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\Pages;

use App\Domain\Enrollment\Services\EnrollmentPriceCalculator;
use App\Enums\EnrollmentMode;
use App\Enums\EnrollmentStatus;
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

        // Set delivery_type based on course if not set (for non-hybrid courses)
        if (!empty($data['course_id']) && empty($data['delivery_type'])) {
            $course = \App\Domain\Training\Models\Course::find($data['course_id']);
            if ($course) {
                $data['delivery_type'] = match ($course->delivery_type) {
                    \App\Domain\Training\Enums\DeliveryType::Onsite => 'onsite',
                    \App\Domain\Training\Enums\DeliveryType::Online => 'online',
                    \App\Domain\Training\Enums\DeliveryType::Virtual => 'online',
                    \App\Domain\Training\Enums\DeliveryType::Hybrid => $data['delivery_type'] ?? 'online',
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

        if (empty($data['enrollment_mode'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'enrollment_mode' => 'Enrollment mode is required.',
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
        }

        // Resolve price using PricingService
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

        // Validate enrollment mode is allowed by pricing mode
        $calculator = app(EnrollmentPriceCalculator::class);
        $enrollmentMode = EnrollmentMode::from($data['enrollment_mode']);
        
        if (!$calculator->validateMode($coursePrice, $enrollmentMode)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'enrollment_mode' => 'This enrollment mode is not allowed for this course pricing configuration.',
            ]);
        }

        // Validate sessions_purchased based on enrollment mode
        if ($enrollmentMode === EnrollmentMode::TRIAL) {
            if (empty($data['sessions_purchased']) || (int) $data['sessions_purchased'] !== 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sessions_purchased' => 'Trial enrollment must have exactly 1 session.',
                ]);
            }
            $data['sessions_purchased'] = 1;
        } elseif ($enrollmentMode === EnrollmentMode::PER_SESSION) {
            $sessionsPurchased = (int) ($data['sessions_purchased'] ?? 0);
            $sessionsCount = $coursePrice->sessions_count ?? 1;
            if ($sessionsPurchased < 1 || $sessionsPurchased > $sessionsCount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sessions_purchased' => "Sessions purchased must be between 1 and {$sessionsCount}.",
                ]);
            }
        } elseif ($enrollmentMode === EnrollmentMode::COURSE_FULL) {
            $data['sessions_purchased'] = null;
        }

        // Calculate price using EnrollmentPriceCalculator
        $sessionsPurchased = $data['sessions_purchased'] ?? null;
        $priceResult = $calculator->calculate($coursePrice, $enrollmentMode, $sessionsPurchased);
        $data['total_amount'] = $priceResult['total_amount'];
        $data['currency_code'] = $priceResult['currency_code'];

        // Set status based on enrollment mode
        if ($enrollmentMode === EnrollmentMode::TRIAL) {
            $data['status'] = EnrollmentStatus::ACTIVE->value;
        } else {
            $data['status'] = EnrollmentStatus::PENDING_PAYMENT->value;
        }

        // Validate installment constraints if pricing_type is installment (only for course_full)
        $pricingType = $data['pricing_type'] ?? 'full';
        if ($pricingType === 'installment' && $enrollmentMode === EnrollmentMode::COURSE_FULL) {
            if (!$coursePrice->allow_installments) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pricing_type' => 'Installment pricing is not allowed for this course.',
                ]);
            }
        }

        // Set created_by
        $data['created_by'] = auth()->id();

        return $data;
    }
}
