<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\Course;
use App\Enums\EnrollmentStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        $statuses = [
            EnrollmentStatus::PENDING->value,
            EnrollmentStatus::ACTIVE->value,
            EnrollmentStatus::COMPLETED->value,
        ];

        $enrollmentCount = 0;
        
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
                
                $enrollment = Enrollment::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'reference' => 'ENR-' . str_pad($enrollmentCount + 1, 6, '0', STR_PAD_LEFT),
                        'status' => $statuses[array_rand($statuses)],
                        'enrolled_at' => $enrollmentDate,
                        'registered_at' => $enrollmentDate->copy()->addHours(rand(1, 24)),
                        'branch_id' => $student->branch_id,
                        'created_by' => $users->random()->id,
                        'updated_by' => $users->random()->id,
                        'notes' => rand(0, 1) ? 'Enrolled via seeder' : null,
                    ]
                );

                $enrollmentCount++;
            }
        }

        $this->command->info("Enrollments seeded successfully! Created {$enrollmentCount} enrollments.");
    }
}
