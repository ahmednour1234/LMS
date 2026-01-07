<?php

namespace App\Services;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use Illuminate\Support\Facades\Log;

/**
 * PriceResolver - Single source of truth for course price resolution.
 * 
 * This service consolidates all pricing logic with deterministic priority:
 * 1. (course + branch + delivery_type) - Most specific match
 * 2. (course + branch + null delivery_type) - Branch-wide price
 * 3. (course + null branch + delivery_type) - Delivery-type specific global
 * 4. (course + null branch + null delivery_type) - Global fallback
 */
class PriceResolver
{
    /**
     * Resolve the most appropriate CoursePrice for given parameters.
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType 'onsite' or 'online'
     * @return CoursePrice|null
     */
    public function resolve(int $courseId, ?int $branchId, string $registrationType): ?CoursePrice
    {
        // Validate registration type
        if (!in_array($registrationType, ['onsite', 'online'])) {
            throw new \InvalidArgumentException(
                "Invalid registration_type: {$registrationType}. Must be 'onsite' or 'online'."
            );
        }

        // Map registration_type to delivery_type enum values
        $deliveryTypeEnums = $this->mapRegistrationTypeToDeliveryTypes($registrationType);

        Log::debug('[PriceResolver] Resolving price', [
            'course_id' => $courseId,
            'branch_id' => $branchId,
            'registration_type' => $registrationType,
            'delivery_types' => array_map(fn($e) => $e->value, $deliveryTypeEnums),
        ]);

        // Priority 1: course + branch + delivery_type (most specific)
        if ($branchId !== null) {
            foreach ($deliveryTypeEnums as $deliveryType) {
                $price = $this->findPrice($courseId, $branchId, $deliveryType);
                if ($price) {
                    Log::debug('[PriceResolver] Found at Priority 1', ['price_id' => $price->id]);
                    return $price;
                }
            }

            // Priority 2: course + branch + null delivery_type
            $price = $this->findPrice($courseId, $branchId, null);
            if ($price) {
                Log::debug('[PriceResolver] Found at Priority 2', ['price_id' => $price->id]);
                return $price;
            }
        }

        // Priority 3: course + null branch + delivery_type
        foreach ($deliveryTypeEnums as $deliveryType) {
            $price = $this->findPrice($courseId, null, $deliveryType);
            if ($price) {
                Log::debug('[PriceResolver] Found at Priority 3', ['price_id' => $price->id]);
                return $price;
            }
        }

        // Priority 4: course + null branch + null delivery_type (global fallback)
        $price = $this->findPrice($courseId, null, null);
        if ($price) {
            Log::debug('[PriceResolver] Found at Priority 4 (global)', ['price_id' => $price->id]);
            return $price;
        }

        Log::warning('[PriceResolver] No price found', [
            'course_id' => $courseId,
            'branch_id' => $branchId,
            'registration_type' => $registrationType,
        ]);

        return null;
    }

    /**
     * Resolve price using Course model instead of ID.
     * 
     * @param Course $course
     * @param int|null $branchId
     * @param string $registrationType
     * @return CoursePrice|null
     */
    public function resolveForCourse(Course $course, ?int $branchId, string $registrationType): ?CoursePrice
    {
        return $this->resolve($course->id, $branchId, $registrationType);
    }

    /**
     * Get formatted pricing data from resolved CoursePrice.
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType
     * @return array|null
     */
    public function getPricingData(int $courseId, ?int $branchId, string $registrationType): ?array
    {
        $coursePrice = $this->resolve($courseId, $branchId, $registrationType);

        if (!$coursePrice) {
            return null;
        }

        return $this->formatPricingData($coursePrice);
    }

    /**
     * Get the effective price amount for enrollment.
     * 
     * For 'course_total' mode: returns the full course price
     * For 'per_session' mode: returns session_price (caller must multiply by qty)
     * For 'both' mode: returns course price (caller decides which to use)
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType
     * @param string $pricingChoice 'full' or 'per_session' (only relevant for 'both' mode)
     * @return float
     * @throws \InvalidArgumentException
     */
    public function getEffectivePrice(
        int $courseId,
        ?int $branchId,
        string $registrationType,
        string $pricingChoice = 'full'
    ): float {
        $coursePrice = $this->resolve($courseId, $branchId, $registrationType);

        if (!$coursePrice) {
            throw new \InvalidArgumentException(
                "No active course price found for course_id={$courseId}, branch_id=" . ($branchId ?? 'null') . 
                ", registration_type={$registrationType}"
            );
        }

        $pricingMode = $coursePrice->pricing_mode ?? 'course_total';

        switch ($pricingMode) {
            case 'course_total':
                return (float) $coursePrice->price;

            case 'per_session':
                return (float) $coursePrice->session_price;

            case 'both':
                // Caller decides which price to use
                if ($pricingChoice === 'per_session') {
                    return (float) $coursePrice->session_price;
                }
                return (float) $coursePrice->price;

            default:
                return (float) $coursePrice->price;
        }
    }

    /**
     * Check if installments are allowed for the resolved price.
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType
     * @return bool
     */
    public function allowsInstallments(int $courseId, ?int $branchId, string $registrationType): bool
    {
        $coursePrice = $this->resolve($courseId, $branchId, $registrationType);

        if (!$coursePrice) {
            return false;
        }

        // Per-session pricing mode does NOT allow installments
        // (paying per session is already a form of splitting payments)
        if ($coursePrice->pricing_mode === 'per_session') {
            return false;
        }

        return (bool) $coursePrice->allow_installments;
    }

    /**
     * Get installment settings for the resolved price.
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType
     * @return array
     */
    public function getInstallmentSettings(int $courseId, ?int $branchId, string $registrationType): array
    {
        $coursePrice = $this->resolve($courseId, $branchId, $registrationType);

        if (!$coursePrice || !$this->allowsInstallments($courseId, $branchId, $registrationType)) {
            return [
                'allow_installments' => false,
                'min_down_payment' => null,
                'max_installments' => null,
            ];
        }

        return [
            'allow_installments' => true,
            'min_down_payment' => $coursePrice->min_down_payment ? (float) $coursePrice->min_down_payment : null,
            'max_installments' => $coursePrice->max_installments,
        ];
    }

    /**
     * Validate that a pricing choice is valid for the resolved CoursePrice.
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param string $registrationType
     * @param string $pricingChoice 'full', 'per_session', or 'installment'
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePricingChoice(
        int $courseId,
        ?int $branchId,
        string $registrationType,
        string $pricingChoice
    ): array {
        $errors = [];
        $coursePrice = $this->resolve($courseId, $branchId, $registrationType);

        if (!$coursePrice) {
            return [
                'valid' => false,
                'errors' => ['No active course price found for this combination.'],
            ];
        }

        $pricingMode = $coursePrice->pricing_mode ?? 'course_total';

        switch ($pricingChoice) {
            case 'full':
                // Full course payment is valid for 'course_total' and 'both' modes
                if ($pricingMode === 'per_session') {
                    $errors[] = 'Full course payment is not available. This course uses per-session pricing only.';
                }
                break;

            case 'per_session':
                // Per-session is valid for 'per_session' and 'both' modes
                if ($pricingMode === 'course_total') {
                    $errors[] = 'Per-session payment is not available. This course uses full course pricing only.';
                }
                break;

            case 'installment':
                // Installments are only valid if allowed and not per_session mode
                if ($pricingMode === 'per_session') {
                    $errors[] = 'Installments are not available for per-session pricing.';
                } elseif (!$coursePrice->allow_installments) {
                    $errors[] = 'Installment payment is not enabled for this course.';
                }
                break;

            default:
                $errors[] = "Invalid pricing choice: {$pricingChoice}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Find a CoursePrice with exact match on all parameters.
     * 
     * @param int $courseId
     * @param int|null $branchId
     * @param DeliveryType|null $deliveryType
     * @return CoursePrice|null
     */
    protected function findPrice(int $courseId, ?int $branchId, ?DeliveryType $deliveryType): ?CoursePrice
    {
        $query = CoursePrice::where('course_id', $courseId)
            ->where('is_active', true);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        } else {
            $query->whereNull('branch_id');
        }

        if ($deliveryType !== null) {
            $query->where('delivery_type', $deliveryType);
        } else {
            $query->whereNull('delivery_type');
        }

        return $query->first();
    }

    /**
     * Map registration type to DeliveryType enum values.
     * 
     * @param string $registrationType
     * @return DeliveryType[]
     */
    protected function mapRegistrationTypeToDeliveryTypes(string $registrationType): array
    {
        return match ($registrationType) {
            'onsite' => [DeliveryType::Onsite],
            'online' => [DeliveryType::Online],
            default => throw new \InvalidArgumentException("Invalid registration_type: {$registrationType}"),
        };
    }

    /**
     * Format CoursePrice data into a standardized array.
     * 
     * @param CoursePrice $coursePrice
     * @return array
     */
    protected function formatPricingData(CoursePrice $coursePrice): array
    {
        return [
            'id' => $coursePrice->id,
            'course_id' => $coursePrice->course_id,
            'branch_id' => $coursePrice->branch_id,
            'delivery_type' => $coursePrice->delivery_type?->value,
            'pricing_mode' => $coursePrice->pricing_mode ?? 'course_total',
            'price' => $coursePrice->price ? (float) $coursePrice->price : null,
            'session_price' => $coursePrice->session_price ? (float) $coursePrice->session_price : null,
            'sessions_count' => $coursePrice->sessions_count,
            'allow_installments' => (bool) $coursePrice->allow_installments,
            'min_down_payment' => $coursePrice->min_down_payment ? (float) $coursePrice->min_down_payment : null,
            'max_installments' => $coursePrice->max_installments,
            'is_active' => (bool) $coursePrice->is_active,
        ];
    }
}

