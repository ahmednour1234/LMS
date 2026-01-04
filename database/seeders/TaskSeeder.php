<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
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

            // Create 2-4 tasks per course
            $tasksCount = rand(2, 4);
            
            for ($i = 1; $i <= $tasksCount; $i++) {
                $submissionTypes = ['file', 'text'];
                $submissionType = $submissionTypes[array_rand($submissionTypes)];
                
                $lessonId = $lessons->isNotEmpty() ? $lessons->random()->id : null;

                $taskData = [
                    'course_id' => $course->id,
                    'lesson_id' => $lessonId,
                    'title' => [
                        'ar' => "مهمة {$i}",
                        'en' => "Task {$i}",
                    ],
                    'description' => [
                        'ar' => "وصف المهمة {$i}",
                        'en' => "Description of task {$i}",
                    ],
                    'submission_type' => $submissionType,
                    'max_score' => rand(50, 100),
                    'due_date' => now()->addDays(rand(7, 30)),
                    'is_active' => true,
                ];

                Task::create($taskData);
            }
        }

        $this->command->info('Tasks seeded successfully!');
    }
}

