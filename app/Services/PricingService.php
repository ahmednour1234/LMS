<?php

namespace App\Services;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Domain\Training\Enums\DeliveryType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PricingService
{
    /**
     * Get course price based on course, branch, registration type, and pricing type
     *
     * @param Course $course
     * @param Branch|null $branch
     * @param string $registrationType 'onsite' or 'online'
     * @param string $pricingType 'full' or 'installment'
     * @return string Decimal price as string
     * @throws ModelNotFoundException If no matching price is found
     */
    public function getCoursePrice(
        Course $course,
        ?Branch $branch,
        string $registrationType,
        string $pricingType
    ): string {
        // Map registration_type to delivery_type for CoursePrice lookup
        // 'onsite' → 'onsite'
        // 'online' → 'online' or 'virtual'
        $deliveryTypes = match ($registrationType) {
            'onsite' => ['onsite'],
            'online' => ['online', 'virtual'],
            default => throw new \InvalidArgumentException("Invalid registration_type: {$registrationType}"),
        };

        // Build query for CoursePrice
        $query = CoursePrice::where('course_id', $course->id)
            ->whereIn('delivery_type', $deliveryTypes)
            ->where('is_active', true);

        // Branch matching: if branch is provided, match it; otherwise allow NULL branch_id
        if ($branch) {
            $query->where(function ($q) use ($branch) {
                $q->where('branch_id', $branch->id)
                  ->orWhereNull('branch_id');
            })->orderByRaw('CASE WHEN branch_id IS NOT NULL THEN 0 ELSE 1 END'); // Prefer branch-specific price
        } else {
            $query->whereNull('branch_id');
        }

        $coursePrice = $query->first();

        if (!$coursePrice) {
            throw new ModelNotFoundException(
                "No active price found for course {$course->id}, " .
                "branch " . ($branch?->id ?? 'null') . ", " .
                "registration_type {$registrationType}"
            );
        }

        // If pricing type is installment, verify it's allowed
        if ($pricingType === 'installment' && !$coursePrice->allow_installments) {
            throw new \InvalidArgumentException(
                "Installment pricing is not allowed for this course/registration type combination"
            );
        }

        return (string) $coursePrice->price;
    }
}

