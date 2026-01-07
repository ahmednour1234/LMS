<?php

namespace Tests\Feature\Enrollment;

use App\Domain\Enrollment\Services\EnrollmentPriceCalculator;
use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Enums\EnrollmentMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected EnrollmentPriceCalculator $calculator;
    protected Course $course;
    protected CoursePrice $coursePrice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new EnrollmentPriceCalculator();
        
        $this->course = Course::factory()->create();
    }

    /**
     * Test course_full calculation
     */
    public function test_calculates_course_full_price(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'course_total',
            'price' => 500.000,
            'is_active' => true,
        ]);

        $result = $this->calculator->calculate($coursePrice, EnrollmentMode::COURSE_FULL);

        $this->assertEquals(500.000, $result['total_amount']);
        $this->assertEquals('OMR', $result['currency_code']);
    }

    /**
     * Test per_session calculation
     */
    public function test_calculates_per_session_price(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $result = $this->calculator->calculate($coursePrice, EnrollmentMode::PER_SESSION, 5);

        $this->assertEquals(250.000, $result['total_amount']); // 50 * 5
        $this->assertEquals('OMR', $result['currency_code']);
    }

    /**
     * Test trial calculation (exactly 1 session)
     */
    public function test_calculates_trial_price(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $result = $this->calculator->calculate($coursePrice, EnrollmentMode::TRIAL, 1);

        $this->assertEquals(50.000, $result['total_amount']); // 50 * 1
        $this->assertEquals('OMR', $result['currency_code']);
    }

    /**
     * Test validation for course_total pricing mode
     */
    public function test_validate_mode_course_total_allows_only_course_full(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'course_total',
            'price' => 500.000,
            'is_active' => true,
        ]);

        $this->assertTrue($this->calculator->validateMode($coursePrice, EnrollmentMode::COURSE_FULL));
        $this->assertFalse($this->calculator->validateMode($coursePrice, EnrollmentMode::PER_SESSION));
        $this->assertFalse($this->calculator->validateMode($coursePrice, EnrollmentMode::TRIAL));
    }

    /**
     * Test validation for per_session pricing mode
     */
    public function test_validate_mode_per_session_allows_per_session_and_trial(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $this->assertFalse($this->calculator->validateMode($coursePrice, EnrollmentMode::COURSE_FULL));
        $this->assertTrue($this->calculator->validateMode($coursePrice, EnrollmentMode::PER_SESSION));
        $this->assertTrue($this->calculator->validateMode($coursePrice, EnrollmentMode::TRIAL));
    }

    /**
     * Test validation for both pricing mode
     */
    public function test_validate_mode_both_allows_all_modes(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'both',
            'price' => 500.000,
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $this->assertTrue($this->calculator->validateMode($coursePrice, EnrollmentMode::COURSE_FULL));
        $this->assertTrue($this->calculator->validateMode($coursePrice, EnrollmentMode::PER_SESSION));
        $this->assertTrue($this->calculator->validateMode($coursePrice, EnrollmentMode::TRIAL));
    }

    /**
     * Test getAllowedModes for course_total
     */
    public function test_get_allowed_modes_course_total(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'course_total',
            'price' => 500.000,
            'is_active' => true,
        ]);

        $allowedModes = $this->calculator->getAllowedModes($coursePrice);

        $this->assertCount(1, $allowedModes);
        $this->assertContains(EnrollmentMode::COURSE_FULL, $allowedModes);
    }

    /**
     * Test getAllowedModes for per_session
     */
    public function test_get_allowed_modes_per_session(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $allowedModes = $this->calculator->getAllowedModes($coursePrice);

        $this->assertCount(2, $allowedModes);
        $this->assertContains(EnrollmentMode::PER_SESSION, $allowedModes);
        $this->assertContains(EnrollmentMode::TRIAL, $allowedModes);
    }

    /**
     * Test getAllowedModes for both
     */
    public function test_get_allowed_modes_both(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'both',
            'price' => 500.000,
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $allowedModes = $this->calculator->getAllowedModes($coursePrice);

        $this->assertCount(3, $allowedModes);
        $this->assertContains(EnrollmentMode::COURSE_FULL, $allowedModes);
        $this->assertContains(EnrollmentMode::PER_SESSION, $allowedModes);
        $this->assertContains(EnrollmentMode::TRIAL, $allowedModes);
    }

    /**
     * Test exception when sessions_purchased is invalid for per_session
     */
    public function test_throws_exception_for_invalid_sessions_purchased(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->calculate($coursePrice, EnrollmentMode::PER_SESSION, 0);
    }
}

