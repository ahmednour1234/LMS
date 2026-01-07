<?php

namespace App\Domain\Enrollment\Services;

use App\Domain\Training\Models\CoursePrice;
use App\Enums\EnrollmentMode;

class EnrollmentPriceCalculator
{
    /**
     * Calculate total amount and currency for an enrollment
     *
     * @param CoursePrice $coursePrice
     * @param EnrollmentMode $enrollmentMode
     * @param int|null $sessionsPurchased
     * @return array{total_amount: float, currency_code: string}
     */
    public function calculate(
        CoursePrice $coursePrice,
        EnrollmentMode $enrollmentMode,
        ?int $sessionsPurchased = null
    ): array {
        $totalAmount = match ($enrollmentMode) {
            EnrollmentMode::COURSE_FULL => (float) $coursePrice->price,
            EnrollmentMode::PER_SESSION, EnrollmentMode::TRIAL => $this->calculateSessionPrice($coursePrice, $sessionsPurchased),
        };

        return [
            'total_amount' => $totalAmount,
            'currency_code' => 'OMR',
        ];
    }

    /**
     * Calculate price for per-session or trial enrollment
     *
     * @param CoursePrice $coursePrice
     * @param int|null $sessionsPurchased
     * @return float
     */
    protected function calculateSessionPrice(CoursePrice $coursePrice, ?int $sessionsPurchased): float
    {
        if ($sessionsPurchased === null || $sessionsPurchased < 1) {
            throw new \InvalidArgumentException('sessions_purchased must be at least 1 for per-session or trial enrollment');
        }

        $sessionPrice = (float) $coursePrice->session_price;
        if ($sessionPrice <= 0) {
            throw new \InvalidArgumentException('Course price must have a valid session_price for per-session or trial enrollment');
        }

        return $sessionPrice * $sessionsPurchased;
    }

    /**
     * Validate if enrollment mode is allowed by course price pricing mode
     *
     * @param CoursePrice $coursePrice
     * @param EnrollmentMode $enrollmentMode
     * @return bool
     */
    public function validateMode(CoursePrice $coursePrice, EnrollmentMode $enrollmentMode): bool
    {
        $pricingMode = $coursePrice->pricing_mode ?? 'course_total';

        return match ($enrollmentMode) {
            EnrollmentMode::COURSE_FULL => in_array($pricingMode, ['course_total', 'both']),
            EnrollmentMode::PER_SESSION => in_array($pricingMode, ['per_session', 'both']),
            EnrollmentMode::TRIAL => in_array($pricingMode, ['per_session', 'both']),
        };
    }

    /**
     * Get allowed enrollment modes for a course price
     *
     * @param CoursePrice $coursePrice
     * @return array<EnrollmentMode>
     */
    public function getAllowedModes(CoursePrice $coursePrice): array
    {
        $pricingMode = $coursePrice->pricing_mode ?? 'course_total';

        return match ($pricingMode) {
            'course_total' => [EnrollmentMode::COURSE_FULL],
            'per_session' => [EnrollmentMode::PER_SESSION, EnrollmentMode::TRIAL],
            'both' => [EnrollmentMode::COURSE_FULL, EnrollmentMode::PER_SESSION, EnrollmentMode::TRIAL],
            default => [],
        };
    }
}

