<?php

namespace App\Domain\Training\Services;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\CoursePrice;

class CoursePriceService
{
    /**
     * Resolve price for a course based on branch and delivery type.
     * 
     * Priority order:
     * 1. Match course + branch + delivery_type
     * 2. Match course + branch + null delivery_type
     * 3. Match course + null branch + delivery_type
     * 4. Match course + null branch + null delivery_type
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param DeliveryType|null $deliveryType
     * @return array|null
     */
    public function resolvePrice(int $courseId, ?int $branchId = null, ?DeliveryType $deliveryType = null): ?array
    {
        // Priority 1: course + branch + delivery_type
        if ($branchId !== null && $deliveryType !== null) {
            $price = CoursePrice::where('course_id', $courseId)
                ->where('branch_id', $branchId)
                ->where('delivery_type', $deliveryType)
                ->where('is_active', true)
                ->first();

            if ($price) {
                return $this->formatPriceResponse($price);
            }
        }

        // Priority 2: course + branch + null delivery_type
        if ($branchId !== null) {
            $price = CoursePrice::where('course_id', $courseId)
                ->where('branch_id', $branchId)
                ->whereNull('delivery_type')
                ->where('is_active', true)
                ->first();

            if ($price) {
                return $this->formatPriceResponse($price);
            }
        }

        // Priority 3: course + null branch + delivery_type
        if ($deliveryType !== null) {
            $price = CoursePrice::where('course_id', $courseId)
                ->whereNull('branch_id')
                ->where('delivery_type', $deliveryType)
                ->where('is_active', true)
                ->first();

            if ($price) {
                return $this->formatPriceResponse($price);
            }
        }

        // Priority 4: course + null branch + null delivery_type
        $price = CoursePrice::where('course_id', $courseId)
            ->whereNull('branch_id')
            ->whereNull('delivery_type')
            ->where('is_active', true)
            ->first();

        if ($price) {
            return $this->formatPriceResponse($price);
        }

        return null;
    }

    /**
     * Format price response array.
     * 
     * @param CoursePrice $price
     * @return array
     */
    private function formatPriceResponse(CoursePrice $price): array
    {
        return [
            'pricing_mode' => $price->pricing_mode ?? 'course_total',
            'course_total_price' => $price->price ? (float) $price->price : null,
            'session_price' => $price->session_price ? (float) $price->session_price : null,
            'sessions_count' => $price->sessions_count,
            'allow_installments' => $price->allow_installments ?? false,
            'is_active' => $price->is_active ?? true,
            'min_down_payment' => $price->min_down_payment ? (float) $price->min_down_payment : null,
            'max_installments' => $price->max_installments,
            'branch_id' => $price->branch_id,
            'delivery_type' => $price->delivery_type?->value,
        ];
    }
}

