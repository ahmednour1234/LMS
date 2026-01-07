<?php

namespace Tests\Feature;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Services\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceResolverTest extends TestCase
{
    use RefreshDatabase;

    protected PriceResolver $priceResolver;
    protected Course $course;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceResolver = new PriceResolver();
        
        // Create test course and branch
        $this->branch = Branch::factory()->create();
        $this->course = Course::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
    }

    /**
     * Test Priority 1: course + branch + delivery_type (most specific)
     */
    public function test_resolves_priority_1_exact_match(): void
    {
        // Create prices at different priority levels
        $priority1Price = CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => $this->branch->id,
            'delivery_type' => DeliveryType::Onsite,
            'pricing_mode' => 'course_total',
            'price' => 100.000,
            'is_active' => true,
        ]);

        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => $this->branch->id,
            'delivery_type' => null,
            'pricing_mode' => 'course_total',
            'price' => 200.000,
            'is_active' => true,
        ]);

        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => DeliveryType::Onsite,
            'pricing_mode' => 'course_total',
            'price' => 300.000,
            'is_active' => true,
        ]);

        $resolved = $this->priceResolver->resolve(
            $this->course->id,
            $this->branch->id,
            'onsite'
        );

        $this->assertNotNull($resolved);
        $this->assertEquals($priority1Price->id, $resolved->id);
        $this->assertEquals(100.000, (float) $resolved->price);
    }

    /**
     * Test Priority 2: course + branch + null delivery_type
     */
    public function test_resolves_priority_2_branch_wide(): void
    {
        // Only create priority 2 and lower
        $priority2Price = CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => $this->branch->id,
            'delivery_type' => null,
            'pricing_mode' => 'course_total',
            'price' => 200.000,
            'is_active' => true,
        ]);

        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => DeliveryType::Onsite,
            'pricing_mode' => 'course_total',
            'price' => 300.000,
            'is_active' => true,
        ]);

        $resolved = $this->priceResolver->resolve(
            $this->course->id,
            $this->branch->id,
            'onsite'
        );

        $this->assertNotNull($resolved);
        $this->assertEquals($priority2Price->id, $resolved->id);
        $this->assertEquals(200.000, (float) $resolved->price);
    }

    /**
     * Test Priority 3: course + null branch + delivery_type
     */
    public function test_resolves_priority_3_global_delivery_type(): void
    {
        // Only create priority 3 and lower
        $priority3Price = CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => DeliveryType::Onsite,
            'pricing_mode' => 'course_total',
            'price' => 300.000,
            'is_active' => true,
        ]);

        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => null,
            'pricing_mode' => 'course_total',
            'price' => 400.000,
            'is_active' => true,
        ]);

        $resolved = $this->priceResolver->resolve(
            $this->course->id,
            $this->branch->id,
            'onsite'
        );

        $this->assertNotNull($resolved);
        $this->assertEquals($priority3Price->id, $resolved->id);
        $this->assertEquals(300.000, (float) $resolved->price);
    }

    /**
     * Test Priority 4: course + null branch + null delivery_type (global fallback)
     */
    public function test_resolves_priority_4_global_fallback(): void
    {
        // Only create global fallback
        $priority4Price = CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => null,
            'pricing_mode' => 'course_total',
            'price' => 400.000,
            'is_active' => true,
        ]);

        $resolved = $this->priceResolver->resolve(
            $this->course->id,
            $this->branch->id,
            'onsite'
        );

        $this->assertNotNull($resolved);
        $this->assertEquals($priority4Price->id, $resolved->id);
        $this->assertEquals(400.000, (float) $resolved->price);
    }

    /**
     * Test that inactive prices are not resolved
     */
    public function test_does_not_resolve_inactive_prices(): void
    {
        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => $this->branch->id,
            'delivery_type' => DeliveryType::Onsite,
            'pricing_mode' => 'course_total',
            'price' => 100.000,
            'is_active' => false, // Inactive
        ]);

        $resolved = $this->priceResolver->resolve(
            $this->course->id,
            $this->branch->id,
            'onsite'
        );

        $this->assertNull($resolved);
    }

    /**
     * Test online registration type maps to Online and Virtual delivery types
     */
    public function test_online_registration_maps_to_online_and_virtual(): void
    {
        // Create Virtual delivery type price
        $virtualPrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => DeliveryType::Virtual,
            'pricing_mode' => 'course_total',
            'price' => 150.000,
            'is_active' => true,
        ]);

        $resolved = $this->priceResolver->resolve(
            $this->course->id,
            null,
            'online'
        );

        $this->assertNotNull($resolved);
        $this->assertEquals($virtualPrice->id, $resolved->id);
    }

    /**
     * Test installments allowed check
     */
    public function test_allows_installments_returns_correct_value(): void
    {
        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => null,
            'pricing_mode' => 'course_total',
            'price' => 500.000,
            'allow_installments' => true,
            'min_down_payment' => 100.000,
            'max_installments' => 6,
            'is_active' => true,
        ]);

        $this->assertTrue(
            $this->priceResolver->allowsInstallments($this->course->id, null, 'onsite')
        );
    }

    /**
     * Test per_session mode does not allow installments
     */
    public function test_per_session_mode_disallows_installments(): void
    {
        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => null,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'allow_installments' => true, // Even if set to true
            'is_active' => true,
        ]);

        $this->assertFalse(
            $this->priceResolver->allowsInstallments($this->course->id, null, 'onsite')
        );
    }

    /**
     * Test validate pricing choice
     */
    public function test_validate_pricing_choice(): void
    {
        CoursePrice::create([
            'course_id' => $this->course->id,
            'branch_id' => null,
            'delivery_type' => null,
            'pricing_mode' => 'course_total',
            'price' => 500.000,
            'allow_installments' => false,
            'is_active' => true,
        ]);

        // Full is valid for course_total
        $result = $this->priceResolver->validatePricingChoice(
            $this->course->id, null, 'onsite', 'full'
        );
        $this->assertTrue($result['valid']);

        // Per-session is NOT valid for course_total
        $result = $this->priceResolver->validatePricingChoice(
            $this->course->id, null, 'onsite', 'per_session'
        );
        $this->assertFalse($result['valid']);

        // Installment is NOT valid when not allowed
        $result = $this->priceResolver->validatePricingChoice(
            $this->course->id, null, 'onsite', 'installment'
        );
        $this->assertFalse($result['valid']);
    }

    /**
     * Test invalid registration type throws exception
     */
    public function test_invalid_registration_type_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->priceResolver->resolve(
            $this->course->id,
            $this->branch->id,
            'invalid_type'
        );
    }
}

