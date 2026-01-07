<?php

namespace Tests\Feature;

use App\Services\InvoiceCalculator;
use Tests\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    protected InvoiceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new InvoiceCalculator();
    }

    /**
     * Test basic calculation without discounts
     */
    public function test_calculates_basic_invoice(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
        ]);

        $this->assertEquals(1000.000, $result['subtotal']);
        $this->assertEquals(0, $result['manual_discount']);
        $this->assertEquals(0, $result['promo_discount']);
        $this->assertEquals(1000.000, $result['total']);
        $this->assertEquals(0, $result['paid_total']);
        $this->assertEquals(1000.000, $result['due_total']);
    }

    /**
     * Test calculation with manual discount
     */
    public function test_calculates_with_manual_discount(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'manual_discount' => 100.000,
        ]);

        $this->assertEquals(1000.000, $result['subtotal']);
        $this->assertEquals(100.000, $result['manual_discount']);
        $this->assertEquals(900.000, $result['total']);
        $this->assertEquals(900.000, $result['due_total']);
    }

    /**
     * Test calculation with promo discount
     */
    public function test_calculates_with_promo_discount(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'promo_discount' => 50.000,
        ]);

        $this->assertEquals(1000.000, $result['subtotal']);
        $this->assertEquals(50.000, $result['promo_discount']);
        $this->assertEquals(950.000, $result['total']);
    }

    /**
     * Test calculation with both discounts
     */
    public function test_calculates_with_both_discounts(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'manual_discount' => 100.000,
            'promo_discount' => 50.000,
        ]);

        $this->assertEquals(150.000, $result['total_discount']);
        $this->assertEquals(850.000, $result['total']);
    }

    /**
     * Test discounts cannot exceed subtotal
     */
    public function test_discounts_capped_at_subtotal(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 100.000,
            'manual_discount' => 80.000,
            'promo_discount' => 50.000, // Would exceed subtotal
        ]);

        // Manual discount takes priority
        $this->assertEquals(80.000, $result['manual_discount']);
        $this->assertEquals(20.000, $result['promo_discount']); // Capped
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test calculation with tax
     */
    public function test_calculates_with_tax(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'tax_rate' => 5, // 5%
        ]);

        $this->assertEquals(1000.000, $result['taxable_amount']);
        $this->assertEquals(50.000, $result['tax_total']);
        $this->assertEquals(1050.000, $result['total']);
    }

    /**
     * Test tax applied after discounts
     */
    public function test_tax_applied_after_discounts(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'manual_discount' => 200.000,
            'tax_rate' => 10, // 10%
        ]);

        $this->assertEquals(800.000, $result['taxable_amount']);
        $this->assertEquals(80.000, $result['tax_total']);
        $this->assertEquals(880.000, $result['total']);
    }

    /**
     * Test paid amount reduces due amount
     */
    public function test_paid_reduces_due(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'paid_total' => 300.000,
        ]);

        $this->assertEquals(1000.000, $result['total']);
        $this->assertEquals(300.000, $result['paid_total']);
        $this->assertEquals(700.000, $result['due_total']);
    }

    /**
     * Test due amount cannot be negative
     */
    public function test_due_amount_never_negative(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'paid_total' => 1500.000, // Overpayment
        ]);

        $this->assertEquals(1000.000, $result['paid_total']); // Capped at total
        $this->assertEquals(0, $result['due_total']);
    }

    /**
     * Test total cannot be negative
     */
    public function test_total_never_negative(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 100.000,
            'manual_discount' => 200.000, // Exceeds subtotal
        ]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
    }

    /**
     * Test status determination - open
     */
    public function test_determine_status_open(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'paid_total' => 0,
        ]);

        $this->assertEquals('open', $this->calculator->determineStatus($result));
    }

    /**
     * Test status determination - partial
     */
    public function test_determine_status_partial(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'paid_total' => 500.000,
        ]);

        $this->assertEquals('partial', $this->calculator->determineStatus($result));
    }

    /**
     * Test status determination - paid
     */
    public function test_determine_status_paid(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 1000.000,
            'paid_total' => 1000.000,
        ]);

        $this->assertEquals('paid', $this->calculator->determineStatus($result));
    }

    /**
     * Test per-session calculation
     */
    public function test_calculate_per_session(): void
    {
        $result = $this->calculator->calculatePerSession(
            sessionPrice: 50.000,
            quantity: 10,
            manualDiscount: 0,
            promoDiscount: 0,
            taxRate: 0,
            paidTotal: 0
        );

        $this->assertEquals(500.000, $result['subtotal']);
        $this->assertEquals(500.000, $result['total']);
    }

    /**
     * Test validate discounts
     */
    public function test_validate_discounts(): void
    {
        // Valid discounts
        $result = $this->calculator->validateDiscounts(1000, 100, 50);
        $this->assertTrue($result['valid']);

        // Manual discount exceeds subtotal
        $result = $this->calculator->validateDiscounts(100, 150, 0);
        $this->assertFalse($result['valid']);

        // Total discounts exceed subtotal
        $result = $this->calculator->validateDiscounts(100, 60, 60);
        $this->assertFalse($result['valid']);

        // Negative discount
        $result = $this->calculator->validateDiscounts(100, -10, 0);
        $this->assertFalse($result['valid']);
    }

    /**
     * Test validate payment
     */
    public function test_validate_payment(): void
    {
        // Valid payment
        $result = $this->calculator->validatePayment(100, 500);
        $this->assertTrue($result['valid']);

        // Payment exceeds due (without allowing overpayment)
        $result = $this->calculator->validatePayment(600, 500, false);
        $this->assertFalse($result['valid']);

        // Payment exceeds due (with allowing overpayment)
        $result = $this->calculator->validatePayment(600, 500, true);
        $this->assertTrue($result['valid']);

        // Zero payment
        $result = $this->calculator->validatePayment(0, 500);
        $this->assertFalse($result['valid']);

        // Invoice already paid
        $result = $this->calculator->validatePayment(100, 0);
        $this->assertFalse($result['valid']);
    }

    /**
     * Test apply promo code - percent
     */
    public function test_apply_promo_code_percent(): void
    {
        $discount = $this->calculator->applyPromoCode(1000, 'percent', 10);
        $this->assertEquals(100.000, $discount);
    }

    /**
     * Test apply promo code - fixed
     */
    public function test_apply_promo_code_fixed(): void
    {
        $discount = $this->calculator->applyPromoCode(1000, 'fixed', 150);
        $this->assertEquals(150.000, $discount);
    }

    /**
     * Test apply promo code - with max discount
     */
    public function test_apply_promo_code_with_max(): void
    {
        $discount = $this->calculator->applyPromoCode(1000, 'percent', 50, 100);
        $this->assertEquals(100.000, $discount); // Capped at max
    }

    /**
     * Test apply promo code - cannot exceed subtotal
     */
    public function test_apply_promo_code_capped_at_subtotal(): void
    {
        $discount = $this->calculator->applyPromoCode(100, 'fixed', 200);
        $this->assertEquals(100.000, $discount); // Capped at subtotal
    }

    /**
     * Test OMR precision (3 decimal places)
     */
    public function test_omr_precision(): void
    {
        $result = $this->calculator->calculate([
            'subtotal' => 100.1234, // More than 3 decimals
            'tax_rate' => 5,
        ]);

        // Should be rounded to 3 decimal places
        $this->assertEquals(100.123, $result['subtotal']);
        $this->assertEquals(5.006, $result['tax_total']); // 100.123 * 0.05 = 5.00615 -> 5.006
    }
}

