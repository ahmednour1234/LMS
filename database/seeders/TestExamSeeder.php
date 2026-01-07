<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\Lesson;
use Illuminate\Database\Seeder;

class TestExamSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     * Creates minimal exams for testing purposes.
     */
    public function run(): void
    {
        $courses = Course::take(2)->get();

        if ($courses->isEmpty()) {
            $this->command->warn('No courses found. Please seed courses first.');
            return;
        }

        foreach ($courses as $course) {
            $lessons = Lesson::whereHas('section', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })->first();

            // Create exactly 1 exam per course for predictable testing
            $examData = [
                'course_id' => $course->id,
                'lesson_id' => $lessons?->id,
                'title' => [
                    'ar' => "امتحان تجريبي",
                    'en' => "Test Exam",
                ],
                'description' => [
                    'ar' => "وصف تجريبي للامتحان",
                    'en' => "Test exam description",
                ],
                'type' => 'mcq',
                'total_score' => 100,
                'duration_minutes' => 60,
                'is_active' => true,
            ];

            // Check if exam already exists for this course
            $existingExam = Exam::where('course_id', $course->id)->first();

            if (!$existingExam) {
                Exam::create($examData);
            }
        }

        $this->command->info('Test exams seeded successfully!');
    }
}

