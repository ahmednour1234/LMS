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
use Illuminate\Support\Facades\Schema;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::where('status', 'active')->get();
        $courses = Course::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $users = User::limit(5)->get();

        if ($students->isEmpty()) {
            $this->command->warn('No active students found. Please seed students first.');
            return;
        }

        if ($courses->isEmpty()) {
            $this->command->warn('No active courses found. Please seed courses first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please create users first.');
            return;
        }

        $pricingService = app(PricingService::class);
        $calculator = app(EnrollmentPriceCalculator::class);
        $enrollmentCount = 0;
        $enrollmentModes = [EnrollmentMode::COURSE_FULL, EnrollmentMode::PER_SESSION, EnrollmentMode::TRIAL];
        
        // Create 2-4 enrollments per student
        foreach ($students as $student) {
            // Get courses from same branch
            $branchCourses = $courses->where('branch_id', $student->branch_id);
            
            if ($branchCourses->isEmpty()) {
                continue;
            }

            // Enroll student in 2-4 random courses from their branch
            $coursesToEnroll = $branchCourses->random(min(rand(2, 4), $branchCourses->count()));

            foreach ($coursesToEnroll as $course) {
                $enrollmentDate = now()->subDays(rand(1, 90));
                
                // Determine delivery type based on course
                $deliveryType = match($course->delivery_type->value) {
                    'onsite' => 'onsite',
                    'online', 'virtual' => 'online',
                    'hybrid' => rand(0, 1) ? 'online' : 'onsite',
                    default => 'online',
                };

                // Get branch for pricing (required for onsite)
                $branchId = $deliveryType === 'onsite' ? $student->branch_id : null;
                
                // Resolve course price
                $coursePrice = $pricingService->resolveCoursePrice($course->id, $branchId, $deliveryType);
                
                if (!$coursePrice) {
                    $this->command->warn("No price found for course {$course->id}, skipping enrollment.");
                    continue;
                }

                // Get allowed enrollment modes
                $allowedModes = $calculator->getAllowedModes($coursePrice);
                
                if (empty($allowedModes)) {
                    $this->command->warn("No allowed enrollment modes for course {$course->id}, skipping enrollment.");
                    continue;
                }

                // Select a random allowed mode
                $enrollmentMode = $allowedModes[array_rand($allowedModes)];
                
                // Determine sessions_purchased and status based on mode
                $sessionsPurchased = null;
                $status = EnrollmentStatus::PENDING_PAYMENT;
                
                if ($enrollmentMode === EnrollmentMode::TRIAL) {
                    $sessionsPurchased = 1;
                    $status = EnrollmentStatus::ACTIVE;
                } elseif ($enrollmentMode === EnrollmentMode::PER_SESSION) {
                    $sessionsCount = $coursePrice->sessions_count ?? 10;
                    $sessionsPurchased = rand(1, min(5, $sessionsCount));
                }

                // Calculate price
                $priceResult = $calculator->calculate($coursePrice, $enrollmentMode, $sessionsPurchased);
                
                $enrollmentData = [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'user_id' => $users->random()->id,
                    'enrollment_mode' => $enrollmentMode->value,
                    'delivery_type' => $deliveryType,
                    'sessions_purchased' => $sessionsPurchased,
                    'currency_code' => $priceResult['currency_code'],
                    'total_amount' => $priceResult['total_amount'],
                    'status' => $status->value,
                    'pricing_type' => 'full', // Default to full payment
                    'enrolled_at' => $enrollmentDate,
                    'branch_id' => $deliveryType === 'onsite' ? $student->branch_id : null,
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                    'notes' => rand(0, 1) ? 'Enrolled via seeder' : null,
                ];

                // Only include registered_at if the column exists
                if (Schema::hasColumn('enrollments', 'registered_at')) {
                    $enrollmentData['registered_at'] = $enrollmentDate->copy()->addHours(rand(1, 24));
                }
                
                $enrollment = Enrollment::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ],
                    $enrollmentData
                );

                $enrollmentCount++;
            }
        }

        $this->command->info("Enrollments seeded successfully! Created {$enrollmentCount} enrollments.");
    }
}
