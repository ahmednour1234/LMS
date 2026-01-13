<?php

namespace App\Domain\Training\Services;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\CoursePrice;
use App\Domain\Training\Models\Course;

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
    public function createCourse(int $teacherId, array $data): Course
{
    $prices = $data['prices'] ?? null;   // NEW
    unset($data['prices']);

    $data['owner_teacher_id'] = $teacherId;
    $data['is_active'] = $data['is_active'] ?? true;

    $course = Course::create($data);

    // create multiple prices in same request
    if (is_array($prices)) {
        foreach ($prices as $priceData) {
            $this->upsertPriceForCourseByType($course, $priceData);
        }
    }

    return $course;
}

/**
 * Upsert CoursePrice by provided delivery_type (online/onsite/hybrid) for branch_id null
 */
public function upsertPriceForCourseByType(Course $course, array $pricingData): CoursePrice
{
    $type = $pricingData['delivery_type'] ?? null;
    if (!$type) {
        // لو حبيت ترمي exception هنا - لكن request rules already guarantee it
        $type = $course->delivery_type?->value ?? (string) $course->delivery_type;
    }

    $deliveryType = $type instanceof \App\Domain\Training\Enums\DeliveryType
        ? $type
        : \App\Domain\Training\Enums\DeliveryType::from((string) $type);

    return CoursePrice::updateOrCreate(
        [
            'course_id' => $course->id,
            'branch_id' => null,
            'delivery_type' => $deliveryType,
        ],
        [
            'pricing_mode' => $pricingData['pricing_mode'] ?? 'course_total',
            'price' => $pricingData['price'] ?? null,
            'session_price' => $pricingData['session_price'] ?? null,
            'sessions_count' => $pricingData['sessions_count'] ?? null,
            'allow_installments' => $pricingData['allow_installments'] ?? false,
            'min_down_payment' => $pricingData['min_down_payment'] ?? null,
            'max_installments' => $pricingData['max_installments'] ?? null,
            'is_active' => $pricingData['is_active'] ?? true,
        ]
    );
}

}

