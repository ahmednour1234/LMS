<?php

namespace App\Services;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Domain\Training\Enums\DeliveryType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

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
     * Resolve CoursePrice model based on course_id, branch_id, and registration_type
     * Follows priority rules with fallbacks
     * 
     * Priority (tried in order):
     * 1) Exact match: course_id + branch_id + delivery_type + is_active=1
     * 2) Fallback: course_id + branch_id + delivery_type NULL + is_active=1
     * 3) Fallback: course_id + branch_id NULL + delivery_type + is_active=1
     * 4) Fallback: course_id + branch_id NULL + delivery_type NULL + is_active=1
     * 
     * Note: For onsite, branch_id is required (validation should enforce this before calling)
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType 'onsite' or 'online'
     * @return CoursePrice|null
     */
    public function resolveCoursePrice(
        int $courseId,
        ?int $branchId,
        string $registrationType
    ): ?CoursePrice {
        // Log inputs
        Log::info('[PRICING_DEBUG] resolveCoursePrice called', [
            'course_id' => $courseId,
            'branch_id' => $branchId,
            'registration_type' => $registrationType,
        ]);

        // Validate registration_type
        if (!in_array($registrationType, ['onsite', 'online'])) {
            throw new \InvalidArgumentException("Invalid registration_type: {$registrationType}. Must be 'onsite' or 'online'.");
        }

        // Map registration_type to delivery_type enum values
        // 'online' registration_type can match both Online and Virtual delivery types
        $deliveryTypeEnums = match ($registrationType) {
            'onsite' => [DeliveryType::Onsite],
            'online' => [DeliveryType::Online, DeliveryType::Virtual],
            default => throw new \InvalidArgumentException("Invalid registration_type: {$registrationType}"),
        };

        // Log mapped delivery_type enum values
        $deliveryTypeValues = array_map(fn($enum) => $enum->value, $deliveryTypeEnums);
        Log::info('[PRICING_DEBUG] Mapped delivery_type enum values', [
            'registration_type' => $registrationType,
            'delivery_type_enums' => $deliveryTypeValues,
        ]);

        // Priority 1: Exact match - course_id + branch_id + delivery_type + is_active=1
        if ($branchId) {
            foreach ($deliveryTypeEnums as $deliveryTypeEnum) {
                $query = CoursePrice::where('course_id', $courseId)
                    ->where('branch_id', $branchId)
                    ->where('delivery_type', $deliveryTypeEnum)
                    ->where('is_active', true);
                
                Log::info('[PRICING_DEBUG] Priority 1 query attempt', [
                    'priority' => 1,
                    'delivery_type' => $deliveryTypeEnum->value,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                ]);

                $price = $query->first();

                if ($price) {
                    Log::info('[PRICING_DEBUG] Price found at Priority 1', [
                        'course_price_id' => $price->id,
                        'price' => $price->price,
                    ]);
                    return $price;
                }

                Log::info('[PRICING_DEBUG] Priority 1 query returned no results', [
                    'delivery_type' => $deliveryTypeEnum->value,
                ]);
            }

            // Priority 2: course_id + branch_id + delivery_type NULL + is_active=1
            $query = CoursePrice::where('course_id', $courseId)
                ->where('branch_id', $branchId)
                ->whereNull('delivery_type')
                ->where('is_active', true);
            
            Log::info('[PRICING_DEBUG] Priority 2 query attempt', [
                'priority' => 2,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            $price = $query->first();

            if ($price) {
                Log::info('[PRICING_DEBUG] Price found at Priority 2', [
                    'course_price_id' => $price->id,
                    'price' => $price->price,
                ]);
                return $price;
            }

            Log::info('[PRICING_DEBUG] Priority 2 query returned no results');
        }

        // Priority 3: course_id + branch_id NULL + delivery_type + is_active=1
        foreach ($deliveryTypeEnums as $deliveryTypeEnum) {
            $query = CoursePrice::where('course_id', $courseId)
                ->whereNull('branch_id')
                ->where('delivery_type', $deliveryTypeEnum)
                ->where('is_active', true);
            
            Log::info('[PRICING_DEBUG] Priority 3 query attempt', [
                'priority' => 3,
                'delivery_type' => $deliveryTypeEnum->value,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            $price = $query->first();

            if ($price) {
                Log::info('[PRICING_DEBUG] Price found at Priority 3', [
                    'course_price_id' => $price->id,
                    'price' => $price->price,
                ]);
                return $price;
            }

            Log::info('[PRICING_DEBUG] Priority 3 query returned no results', [
                'delivery_type' => $deliveryTypeEnum->value,
            ]);
        }

        // Priority 4: course_id + branch_id NULL + delivery_type NULL + is_active=1 (global fallback)
        $query = CoursePrice::where('course_id', $courseId)
            ->whereNull('branch_id')
            ->whereNull('delivery_type')
            ->where('is_active', true);
        
        Log::info('[PRICING_DEBUG] Priority 4 query attempt', [
            'priority' => 4,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $price = $query->first();

        if ($price) {
            Log::info('[PRICING_DEBUG] Price found at Priority 4', [
                'course_price_id' => $price->id,
                'price' => $price->price,
            ]);
            return $price;
        }

        Log::info('[PRICING_DEBUG] Priority 4 query returned no results - no price found');
        Log::info('[PRICING_DEBUG] resolveCoursePrice returning null - all priority queries exhausted');

        return $price;
    }

    /**
     * Get enrollment amount from resolved CoursePrice
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType
     * @return float
     * @throws \Illuminate\Validation\ValidationException
     */
    public function getEnrollmentAmount(
        int $courseId,
        ?int $branchId,
        string $registrationType
    ): float {
        $coursePrice = $this->resolveCoursePrice($courseId, $branchId, $registrationType);

        if (!$coursePrice) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'No active course price found for this course/branch/delivery type combination.',
            ]);
        }

        return (float) $coursePrice->price;
    }
}

