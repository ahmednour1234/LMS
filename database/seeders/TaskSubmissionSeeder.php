<?php

namespace Database\Seeders;

use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TaskSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = Task::all();
        $students = Student::all();

        if ($tasks->isEmpty()) {
            $this->command->warn('No tasks found. Please seed tasks first.');
            return;
        }

        if ($students->isEmpty()) {
            $this->command->warn('No students found. Please seed students first.');
            return;
        }

        $teachers = collect();
        try {
            $role = Role::findByName('trainer', 'web');
            $teachers = User::role($role)->get();
        } catch (\Exception $e) {
            // Role doesn't exist, fall back to all users
        }
        if ($teachers->isEmpty()) {
            $teachers = User::all();
        }

        foreach ($tasks as $task) {
            // Get enrolled students for this course
            $enrolledStudents = $students->filter(function ($student) use ($task) {
                return $student->enrollments()->where('course_id', $task->course_id)->exists();
            });

            if ($enrolledStudents->isEmpty()) {
                continue;
            }

            // Create submissions for 30-70% of enrolled students
            $submissionCount = max(1, (int) ($enrolledStudents->count() * (rand(30, 70) / 100)));
            $selectedStudents = $enrolledStudents->random(min($submissionCount, $enrolledStudents->count()));

            foreach ($selectedStudents as $student) {
                $statuses = ['submitted', 'graded', 'pending'];
                $status = $statuses[array_rand($statuses)];

                $submissionData = [
                    'task_id' => $task->id,
                    'student_id' => $student->id,
                    'status' => $status,
                ];

                if ($task->submission_type === 'text') {
                    $submissionData['submission_text'] = 'Sample submission text';
                }

                // If graded, add score and reviewer
                if ($status === 'graded') {
                    $submissionData['score'] = rand(0, (int) $task->max_score);
                    $submissionData['feedback'] = [
                        'ar' => 'ملاحظات التقييم',
                        'en' => 'Grading feedback',
                    ];
                    $submissionData['reviewed_at'] = now();
                    $submissionData['reviewed_by'] = $teachers->isNotEmpty() ? $teachers->random()->id : null;
                }

                TaskSubmission::firstOrCreate(
                    [
                        'task_id' => $task->id,
                        'student_id' => $student->id,
                    ],
                    $submissionData
                );
            }
        }

        $this->command->info('Task submissions seeded successfully!');
    }
}

