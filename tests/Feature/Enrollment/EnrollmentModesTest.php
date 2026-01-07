<?php

namespace Tests\Feature\Enrollment;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Enums\EnrollmentMode;
use App\Enums\EnrollmentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentModesTest extends TestCase
{
    use RefreshDatabase;

    protected Course $course;
    protected Student $student;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->student = Student::factory()->create();
        $this->course = Course::factory()->create();
    }

    /**
     * Test course_full enrollment creation
     */
    public function test_creates_course_full_enrollment(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'course_total',
            'price' => 500.000,
            'is_active' => true,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_mode' => EnrollmentMode::COURSE_FULL,
            'delivery_type' => 'online',
            'total_amount' => 500.000,
            'currency_code' => 'OMR',
            'status' => EnrollmentStatus::PENDING_PAYMENT,
            'sessions_purchased' => null,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'enrollment_mode' => 'course_full',
            'total_amount' => 500.000,
            'currency_code' => 'OMR',
            'status' => 'pending_payment',
            'sessions_purchased' => null,
        ]);

        $this->assertTrue($enrollment->isCourseFull());
        $this->assertFalse($enrollment->isPerSession());
        $this->assertFalse($enrollment->isTrial());
    }

    /**
     * Test per_session enrollment creation
     */
    public function test_creates_per_session_enrollment(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_mode' => EnrollmentMode::PER_SESSION,
            'delivery_type' => 'online',
            'total_amount' => 250.000, // 50 * 5
            'currency_code' => 'OMR',
            'status' => EnrollmentStatus::PENDING_PAYMENT,
            'sessions_purchased' => 5,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'enrollment_mode' => 'per_session',
            'total_amount' => 250.000,
            'currency_code' => 'OMR',
            'status' => 'pending_payment',
            'sessions_purchased' => 5,
        ]);

        $this->assertFalse($enrollment->isCourseFull());
        $this->assertTrue($enrollment->isPerSession());
        $this->assertFalse($enrollment->isTrial());
    }

    /**
     * Test trial enrollment creation
     */
    public function test_creates_trial_enrollment(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_mode' => EnrollmentMode::TRIAL,
            'delivery_type' => 'online',
            'total_amount' => 50.000, // 50 * 1
            'currency_code' => 'OMR',
            'status' => EnrollmentStatus::ACTIVE,
            'sessions_purchased' => 1,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'enrollment_mode' => 'trial',
            'total_amount' => 50.000,
            'currency_code' => 'OMR',
            'status' => 'active',
            'sessions_purchased' => 1,
        ]);

        $this->assertFalse($enrollment->isCourseFull());
        $this->assertFalse($enrollment->isPerSession());
        $this->assertTrue($enrollment->isTrial());
    }

    /**
     * Test that trial enrollment has status = active
     */
    public function test_trial_enrollment_has_active_status(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'per_session',
            'session_price' => 50.000,
            'sessions_count' => 10,
            'is_active' => true,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_mode' => EnrollmentMode::TRIAL,
            'delivery_type' => 'online',
            'total_amount' => 50.000,
            'currency_code' => 'OMR',
            'status' => EnrollmentStatus::ACTIVE,
            'sessions_purchased' => 1,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
    }

    /**
     * Test that non-trial enrollments have status = pending_payment
     */
    public function test_non_trial_enrollment_has_pending_payment_status(): void
    {
        $coursePrice = CoursePrice::create([
            'course_id' => $this->course->id,
            'pricing_mode' => 'course_total',
            'price' => 500.000,
            'is_active' => true,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_mode' => EnrollmentMode::COURSE_FULL,
            'delivery_type' => 'online',
            'total_amount' => 500.000,
            'currency_code' => 'OMR',
            'status' => EnrollmentStatus::PENDING_PAYMENT,
            'sessions_purchased' => null,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(EnrollmentStatus::PENDING_PAYMENT, $enrollment->status);
    }
}

