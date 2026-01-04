<?php

namespace App\Domain\Training\Services;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;

class PricingService
{
    /**
     * Get effective price for a course in a branch and delivery type.
     * Checks branch/delivery-specific price first, falls back to global price.
     */
    public function getPrice(Course $course, ?int $branchId = null, ?string $deliveryType = null): string
    {
        $query = CoursePrice::where('course_id', $course->id)
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        } else {
            $query->whereNull('branch_id');
        }

        if ($deliveryType) {
            $query->where(function ($q) use ($deliveryType) {
                $q->where('delivery_type', $deliveryType)
                    ->orWhereNull('delivery_type');
            });
        } else {
            $query->whereNull('delivery_type');
        }

        $price = $query->orderBy('branch_id', 'desc')
            ->orderBy('delivery_type', 'desc')
            ->first();

        if ($price) {
            return (string) $price->price;
        }

        return '0.00';
    }

    /**
     * Get effective installment settings for a course in a branch and delivery type.
     */
    public function getInstallmentSettings(Course $course, ?int $branchId = null, ?string $deliveryType = null): array
    {
        $query = CoursePrice::where('course_id', $course->id)
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        } else {
            $query->whereNull('branch_id');
        }

        if ($deliveryType) {
            $query->where(function ($q) use ($deliveryType) {
                $q->where('delivery_type', $deliveryType)
                    ->orWhereNull('delivery_type');
            });
        } else {
            $query->whereNull('delivery_type');
        }

        $price = $query->orderBy('branch_id', 'desc')
            ->orderBy('delivery_type', 'desc')
            ->first();

        if ($price && $price->allow_installments) {
            return [
                'allow_installments' => true,
                'min_down_payment' => $price->min_down_payment,
                'max_installments' => $price->max_installments,
            ];
        }

        return ['allow_installments' => false];
    }

    /**
     * Get pricing details for a course in a branch and delivery type.
     */
    public function getPricing(Course $course, ?int $branchId = null, ?string $deliveryType = null): array
    {
        return [
            'price' => $this->getPrice($course, $branchId, $deliveryType),
            ...$this->getInstallmentSettings($course, $branchId, $deliveryType),
        ];
    }
}
