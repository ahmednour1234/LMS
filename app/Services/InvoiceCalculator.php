<?php

namespace App\Services;

use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Enrollment\Models\Enrollment;

/**
 * InvoiceCalculator - Single source of truth for invoice calculations.
 * 
 * This service centralizes all invoice math to ensure consistency across:
 * - Filament UI reactive callbacks
 * - Model accessors
 * - PDF generation
 * - API responses
 * 
 * Invariants enforced:
 * - total >= 0
 * - paid_total <= total
 * - discounts <= subtotal
 * - due_total = max(0, total - paid_total)
 */
class InvoiceCalculator
{
    /**
     * Calculate invoice totals from draft data (before invoice creation).
     * 
     * @param array $data Invoice draft data containing:
     *   - subtotal: float (base price before discounts)
     *   - manual_discount: float (optional)
     *   - promo_discount: float (optional)
     *   - tax_rate: float (optional, as percentage e.g. 5 for 5%)
     *   - paid_total: float (optional, for existing invoices)
     * @return array Calculated values
     */
    public function calculate(array $data): array
    {
        $subtotal = max(0, (float) ($data['subtotal'] ?? 0));
        $manualDiscount = max(0, (float) ($data['manual_discount'] ?? 0));
        $promoDiscount = max(0, (float) ($data['promo_discount'] ?? 0));
        $taxRate = max(0, (float) ($data['tax_rate'] ?? 0));
        $paidTotal = max(0, (float) ($data['paid_total'] ?? 0));

        // Apply discount order: manual_discount first, then promo_discount
        // Discounts cannot exceed subtotal
        $totalDiscount = min($subtotal, $manualDiscount + $promoDiscount);
        
        // Adjust individual discounts if they exceed subtotal
        if ($manualDiscount + $promoDiscount > $subtotal) {
            // Manual discount takes priority
            $manualDiscount = min($manualDiscount, $subtotal);
            $promoDiscount = min($promoDiscount, $subtotal - $manualDiscount);
        }

        // Calculate taxable amount (after discounts)
        $taxableAmount = $subtotal - $manualDiscount - $promoDiscount;
        
        // Calculate tax
        $taxTotal = $this->round($taxableAmount * ($taxRate / 100));

        // Calculate total
        $total = max(0, $this->round($taxableAmount + $taxTotal));

        // Ensure paid_total doesn't exceed total
        $paidTotal = min($paidTotal, $total);

        // Calculate due amount
        $dueTotal = max(0, $this->round($total - $paidTotal));

        return [
            'subtotal' => $this->round($subtotal),
            'manual_discount' => $this->round($manualDiscount),
            'promo_discount' => $this->round($promoDiscount),
            'total_discount' => $this->round($manualDiscount + $promoDiscount),
            'taxable_amount' => $this->round($taxableAmount),
            'tax_rate' => $taxRate,
            'tax_total' => $taxTotal,
            'total' => $total,
            'paid_total' => $this->round($paidTotal),
            'due_total' => $dueTotal,
        ];
    }

    /**
     * Calculate totals for an existing ArInvoice.
     * 
     * @param ArInvoice $invoice
     * @return array
     */
    public function calculateForInvoice(ArInvoice $invoice): array
    {
        // Get paid amount from payments
        $paidTotal = Payment::where('enrollment_id', $invoice->enrollment_id)
            ->where('status', 'paid')
            ->sum('amount');

        return $this->calculate([
            'subtotal' => (float) ($invoice->subtotal ?? $invoice->total_amount),
            'manual_discount' => (float) ($invoice->manual_discount ?? 0),
            'promo_discount' => (float) ($invoice->promo_discount ?? 0),
            'tax_rate' => (float) ($invoice->tax_rate ?? 0),
            'paid_total' => (float) $paidTotal,
        ]);
    }

    /**
     * Calculate totals for an Enrollment (before invoice exists).
     * 
     * @param Enrollment $enrollment
     * @param float $manualDiscount
     * @param float $promoDiscount
     * @param float $taxRate
     * @return array
     */
    public function calculateForEnrollment(
        Enrollment $enrollment,
        float $manualDiscount = 0,
        float $promoDiscount = 0,
        float $taxRate = 0
    ): array {
        $paidTotal = $enrollment->payments()
            ->where('status', 'paid')
            ->sum('amount');

        return $this->calculate([
            'subtotal' => (float) $enrollment->total_amount,
            'manual_discount' => $manualDiscount,
            'promo_discount' => $promoDiscount,
            'tax_rate' => $taxRate,
            'paid_total' => (float) $paidTotal,
        ]);
    }

    /**
     * Calculate per-session invoice totals.
     * 
     * @param float $sessionPrice Price per session
     * @param int $quantity Number of sessions
     * @param float $manualDiscount
     * @param float $promoDiscount
     * @param float $taxRate
     * @param float $paidTotal
     * @return array
     */
    public function calculatePerSession(
        float $sessionPrice,
        int $quantity,
        float $manualDiscount = 0,
        float $promoDiscount = 0,
        float $taxRate = 0,
        float $paidTotal = 0
    ): array {
        $subtotal = $sessionPrice * $quantity;

        return $this->calculate([
            'subtotal' => $subtotal,
            'manual_discount' => $manualDiscount,
            'promo_discount' => $promoDiscount,
            'tax_rate' => $taxRate,
            'paid_total' => $paidTotal,
        ]);
    }

    /**
     * Determine invoice status based on calculated values.
     * 
     * @param array $calculated Result from calculate()
     * @return string 'open', 'partial', or 'paid'
     */
    public function determineStatus(array $calculated): string
    {
        $total = $calculated['total'];
        $dueTotal = $calculated['due_total'];

        if ($dueTotal <= 0) {
            return 'paid';
        }

        if ($dueTotal < $total) {
            return 'partial';
        }

        return 'open';
    }

    /**
     * Validate discount amounts against subtotal.
     * 
     * @param float $subtotal
     * @param float $manualDiscount
     * @param float $promoDiscount
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateDiscounts(float $subtotal, float $manualDiscount, float $promoDiscount): array
    {
        $errors = [];

        if ($manualDiscount < 0) {
            $errors[] = 'Manual discount cannot be negative.';
        }

        if ($promoDiscount < 0) {
            $errors[] = 'Promo discount cannot be negative.';
        }

        if ($manualDiscount > $subtotal) {
            $errors[] = 'Manual discount cannot exceed subtotal.';
        }

        if ($manualDiscount + $promoDiscount > $subtotal) {
            $errors[] = 'Total discounts cannot exceed subtotal.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate payment amount.
     * 
     * @param float $paymentAmount
     * @param float $dueTotal
     * @param bool $allowOverpayment
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePayment(float $paymentAmount, float $dueTotal, bool $allowOverpayment = false): array
    {
        $errors = [];

        if ($paymentAmount <= 0) {
            $errors[] = 'Payment amount must be greater than zero.';
        }

        if (!$allowOverpayment && $paymentAmount > $dueTotal) {
            $errors[] = 'Payment amount cannot exceed due amount.';
        }

        if ($dueTotal <= 0) {
            $errors[] = 'Invoice is already fully paid.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Apply promo code discount to subtotal.
     * 
     * @param float $subtotal
     * @param string $discountType 'percent' or 'fixed'
     * @param float $discountValue
     * @param float $maxDiscount Maximum discount allowed (optional)
     * @return float The discount amount
     */
    public function applyPromoCode(
        float $subtotal,
        string $discountType,
        float $discountValue,
        ?float $maxDiscount = null
    ): float {
        $discount = match ($discountType) {
            'percent' => $this->round($subtotal * ($discountValue / 100)),
            'fixed' => $discountValue,
            default => 0,
        };

        // Apply max discount cap if set
        if ($maxDiscount !== null) {
            $discount = min($discount, $maxDiscount);
        }

        // Discount cannot exceed subtotal
        return min($discount, $subtotal);
    }

    /**
     * Round monetary value to configured precision.
     * Uses OMR precision (3 decimal places) by default.
     * 
     * @param float $value
     * @return float
     */
    protected function round(float $value): float
    {
        $precision = config('money.precision', 3);
        return round($value, $precision);
    }

    /**
     * Format monetary value for display.
     * 
     * @param float $value
     * @param bool $includeSymbol
     * @return string
     */
    public function format(float $value, bool $includeSymbol = true): string
    {
        $precision = config('money.precision', 3);
        $symbol = config('money.symbol', 'ر.ع');
        
        $formatted = number_format($value, $precision);
        
        if ($includeSymbol) {
            // For RTL languages, symbol comes after the number
            return $formatted . ' ' . $symbol;
        }
        
        return $formatted;
    }
}

