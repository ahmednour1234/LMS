<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Enrollment\Services\EnrollmentPriceCalculator;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Enums\EnrollmentMode;
use App\Enums\EnrollmentStatus;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Database\Seeder;

/**
 * Test seeder for enrollment modes
 * Creates test enrollments for all 3 enrollment modes (course_full, per_session, trial)
 */
class EnrollmentTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Enrollment Test Seeder...');

        // Get required data
        $students = Student::where('status', 'active')->withoutGlobalScopes()->limit(3)->get();
        $courses = Course::where('is_active', true)->get();
        $users = User::limit(3)->get();

        if ($students->isEmpty()) {
            $this->command->error('No active students found. Please seed students first.');
            return;
        }

        if ($courses->isEmpty()) {
            $this->command->error('No active courses found. Please seed courses first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please create users first.');
            return;
        }

        $pricingService = app(PricingService::class);
        $calculator = app(EnrollmentPriceCalculator::class);
        $enrollmentCount = 0;

        // Test each enrollment mode
        $modes = [
            EnrollmentMode::COURSE_FULL => 'Course Full',
            EnrollmentMode::PER_SESSION => 'Per Session',
            EnrollmentMode::TRIAL => 'Trial',
        ];

        foreach ($modes as $mode => $modeLabel) {
            $this->command->info("Creating test enrollments for mode: {$modeLabel}");

            // Find a course with appropriate pricing mode
            $course = $this->findCourseForMode($courses, $mode, $pricingService, $calculator);
            
            if (!$course) {
                $this->command->warn("No suitable course found for {$modeLabel} mode, skipping...");
                continue;
            }

            // Get a student
            $student = $students->random();
            
            // Determine delivery type
            $deliveryType = match($course->delivery_type->value) {
                'onsite' => 'onsite',
                'online' => 'online',
                'hybrid' => 'online',
                default => 'online',
            };

            $branchId = $deliveryType === 'onsite' ? $student->branch_id : null;
            $coursePrice = $pricingService->resolveCoursePrice($course->id, $branchId, $deliveryType);

            if (!$coursePrice) {
                $this->command->warn("No price found for course {$course->id}, skipping...");
                continue;
            }

            // Set up enrollment data based on mode
            $sessionsPurchased = null;
            $status = EnrollmentStatus::PENDING_PAYMENT;

            if ($mode === EnrollmentMode::TRIAL) {
                $sessionsPurchased = 1;
                $status = EnrollmentStatus::ACTIVE;
            } elseif ($mode === EnrollmentMode::PER_SESSION) {
                $sessionsCount = $coursePrice->sessions_count ?? 10;
                $sessionsPurchased = rand(1, min(5, $sessionsCount));
            }

            // Calculate price
            $priceResult = $calculator->calculate($coursePrice, $mode, $sessionsPurchased);

            $enrollmentData = [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'user_id' => $users->random()->id,
                'enrollment_mode' => $mode->value,
                'delivery_type' => $deliveryType,
                'sessions_purchased' => $sessionsPurchased,
                'currency_code' => $priceResult['currency_code'],
                'total_amount' => $priceResult['total_amount'],
                'status' => $status->value,
                'pricing_type' => 'full',
                'enrolled_at' => now()->subDays(rand(1, 30)),
                'branch_id' => $deliveryType === 'onsite' ? $student->branch_id : null,
                'created_by' => $users->random()->id,
                'updated_by' => $users->random()->id,
                'notes' => "Test enrollment for {$modeLabel} mode",
            ];

            $enrollment = Enrollment::create($enrollmentData);
            $enrollmentCount++;

            $this->command->info("  ✓ Created enrollment #{$enrollment->id} for {$modeLabel} mode");
            $this->command->info("    - Course: {$course->code}");
            $this->command->info("    - Student: {$student->name}");
            $this->command->info("    - Total: {$priceResult['total_amount']} {$priceResult['currency_code']}");
            if ($sessionsPurchased) {
                $this->command->info("    - Sessions: {$sessionsPurchased}");
            }
        }

        $this->command->info("\nEnrollment Test Seeder completed! Created {$enrollmentCount} test enrollments.");
    }

    /**
     * Find a course that supports the given enrollment mode
     */
    protected function findCourseForMode($courses, EnrollmentMode $mode, PricingService $pricingService, EnrollmentPriceCalculator $calculator): ?Course
    {
        foreach ($courses as $course) {
            // Try to find a price that supports this mode
            $deliveryType = match($course->delivery_type->value) {
                'onsite' => 'onsite',
                'online' => 'online',
                'hybrid' => 'online',
                default => 'online',
            };

            // Try with null branch first (global pricing)
            $coursePrice = $pricingService->resolveCoursePrice($course->id, null, $deliveryType);
            
            if ($coursePrice && $calculator->validateMode($coursePrice, $mode)) {
                return $course;
            }

            // Try with a branch
            $branches = Branch::where('is_active', true)->get();
            foreach ($branches as $branch) {
                $coursePrice = $pricingService->resolveCoursePrice($course->id, $branch->id, $deliveryType);
                if ($coursePrice && $calculator->validateMode($coursePrice, $mode)) {
                    return $course;
                }
            }
        }

        return null;
    }
}

