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

        // Calculate total_amount using PricingService
        if (!empty($data['course_id'])) {
            try {
                $course = \App\Domain\Training\Models\Course::find($data['course_id']);
                $branch = !empty($data['branch_id']) ? \App\Domain\Branch\Models\Branch::find($data['branch_id']) : null;
                $registrationType = $data['registration_type'] ?? 'online';
                $pricingType = $data['pricing_type'] ?? 'full';

                $pricingService = app(\App\Services\PricingService::class);
                $data['total_amount'] = $pricingService->getCoursePrice($course, $branch, $registrationType, $pricingType);
            } catch (\Exception $e) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'course_id' => 'Unable to determine price for this course. Please ensure pricing is configured: ' . $e->getMessage(),
                ]);
            }
        }

        // Set created_by
        $data['created_by'] = auth()->id();

        return $data;
    }
}
