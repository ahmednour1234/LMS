<?php

namespace App\Domain\Training\Services;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseBranchPrice;

class PricingService
{
    /**
     * Get effective price for a course in a branch.
     * Checks branch-specific price first, falls back to course default price.
     */
    public function getPrice(Course $course, int $branchId): string
    {
        $branchPrice = CourseBranchPrice::where('course_id', $course->id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if ($branchPrice) {
            return (string) $branchPrice->price;
        }

        return (string) $course->price;
    }

    /**
     * Get effective installment enabled status for a course in a branch.
     * Checks branch-specific setting first, falls back to course default.
     */
    public function isInstallmentEnabled(Course $course, int $branchId): bool
    {
        $branchPrice = CourseBranchPrice::where('course_id', $course->id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if ($branchPrice) {
            return $branchPrice->is_installment_enabled;
        }

        return $course->is_installment_enabled;
    }

    /**
     * Get pricing details for a course in a branch.
     * Returns array with price and is_installment_enabled.
     */
    public function getPricing(Course $course, int $branchId): array
    {
        return [
            'price' => $this->getPrice($course, $branchId),
            'is_installment_enabled' => $this->isInstallmentEnabled($course, $branchId),
        ];
    }
}

