<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\Lesson;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->command->warn('No courses found. Please seed courses first.');
            return;
        }

        foreach ($courses as $course) {
            $lessons = Lesson::whereHas('section', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })->get();

            // Check how many exams already exist for this course
            $existingExamsCount = Exam::where('course_id', $course->id)->count();
            $targetExamsCount = rand(1, 2);
            $examsToCreate = max(0, $targetExamsCount - $existingExamsCount);
            
            for ($i = 1; $i <= $examsToCreate; $i++) {
                $examTypes = ['mcq', 'essay', 'mixed'];
                $type = $examTypes[array_rand($examTypes)];
                
                $lessonId = $lessons->isNotEmpty() ? $lessons->random()->id : null;

                $examNumber = $existingExamsCount + $i;
                
                $examData = [
                    'course_id' => $course->id,
                    'lesson_id' => $lessonId,
                    'title' => [
                        'ar' => "امتحان {$examNumber}",
                        'en' => "Exam {$examNumber}",
                    ],
                    'description' => [
                        'ar' => "وصف الامتحان {$examNumber}",
                        'en' => "Description of exam {$examNumber}",
                    ],
                    'type' => $type,
                    'total_score' => rand(50, 100),
                    'duration_minutes' => rand(30, 120),
                    'is_active' => true,
                ];

                Exam::create($examData);
            }
        }

        $this->command->info('Exams seeded successfully!');
    }
}

