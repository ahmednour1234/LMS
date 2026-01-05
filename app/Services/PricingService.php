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

    /**
     * Resolve CoursePrice model based on course_id, branch_id, and delivery_type
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $deliveryType 'onsite' or 'online' (from registration_type)
     * @return CoursePrice|null
     */
    public function resolveCoursePrice(
        int $courseId,
        ?int $branchId,
        string $deliveryType
    ): ?CoursePrice {
        // Map registration_type/delivery_type to CoursePrice delivery_type enum values
        $deliveryTypeEnums = match ($deliveryType) {
            'onsite' => [DeliveryType::Onsite],
            'online' => [DeliveryType::Online, DeliveryType::Virtual],
            default => throw new \InvalidArgumentException("Invalid delivery_type: {$deliveryType}"),
        };

        // Build query for CoursePrice
        $query = CoursePrice::where('course_id', $courseId)
            ->whereIn('delivery_type', $deliveryTypeEnums)
            ->where('is_active', true);

        // Branch matching logic:
        // - For onsite: require branch_id, no NULL fallback
        // - For online: allow NULL branch_id fallback
        if ($deliveryType === 'onsite') {
            // Onsite requires branch_id - no fallback to NULL
            if ($branchId) {
                $query->where('branch_id', $branchId);
            } else {
                // No branch_id for onsite - return null (validation will catch this)
                return null;
            }
        } else {
            // Online allows NULL branch_id fallback
            if ($branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })->orderByRaw('CASE WHEN branch_id IS NOT NULL THEN 0 ELSE 1 END'); // Prefer branch-specific
            } else {
                $query->whereNull('branch_id');
            }
        }

        return $query->first();
    }
}

