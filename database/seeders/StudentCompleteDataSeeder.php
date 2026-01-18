<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\Program;
use App\Domain\Training\Models\Teacher;
use App\Domain\Training\Enums\LessonType;
use App\Enums\EnrollmentMode;
use App\Enums\EnrollmentStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentCompleteDataSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('is_active', true)->first();
        if (!$branch) {
            $this->command->error('No active branch found. Please seed branches first.');
            return;
        }

        $program = Program::where('branch_id', $branch->id)->first();
        if (!$program) {
            $this->command->error('No program found. Please seed programs first.');
            return;
        }

        $teacher = Teacher::first();
        if (!$teacher) {
            $this->command->error('No teacher found. Please seed teachers first.');
            return;
        }

        $user = User::first();
        if (!$user) {
            $this->command->error('No user found. Please create users first.');
            return;
        }

        $student = Student::firstOrCreate(
            ['email' => 'complete.student@example.com'],
            [
                'branch_id' => $branch->id,
                'name' => 'Complete Student',
                'student_code' => 'STU-COMPLETE-001',
                'national_id' => '99999999999999',
                'phone' => '01009999999',
                'email' => 'complete.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $course = Course::firstOrCreate(
            ['code' => 'COURSE-COMPLETE-001'],
            [
                'program_id' => $program->id,
                'owner_teacher_id' => $teacher->id,
                'code' => 'COURSE-COMPLETE-001',
                'name' => ['ar' => 'دورة كاملة', 'en' => 'Complete Course'],
                'description' => ['ar' => 'دورة شاملة للطالب', 'en' => 'Complete course for student'],
                'delivery_type' => 'online',
                'duration_hours' => 40,
                'is_active' => true,
            ]
        );

        $section = CourseSection::firstOrCreate(
            [
                'course_id' => $course->id,
                'order' => 1,
            ],
            [
                'course_id' => $course->id,
                'title' => ['ar' => 'القسم الأول', 'en' => 'Section One'],
                'description' => ['ar' => 'وصف القسم', 'en' => 'Section description'],
                'order' => 1,
                'is_active' => true,
            ]
        );

        $lesson1 = Lesson::firstOrCreate(
            [
                'section_id' => $section->id,
                'sort_order' => 1,
            ],
            [
                'section_id' => $section->id,
                'title' => ['ar' => 'الدرس الأول', 'en' => 'Lesson One'],
                'description' => ['ar' => 'وصف الدرس الأول', 'en' => 'First lesson description'],
                'lesson_type' => LessonType::RECORDED,
                'sort_order' => 1,
                'estimated_minutes' => 45,
                'is_preview' => true,
                'is_active' => true,
                'published_at' => now(),
            ]
        );

        $lesson2 = Lesson::firstOrCreate(
            [
                'section_id' => $section->id,
                'sort_order' => 2,
            ],
            [
                'section_id' => $section->id,
                'title' => ['ar' => 'الدرس الثاني', 'en' => 'Lesson Two'],
                'description' => ['ar' => 'وصف الدرس الثاني', 'en' => 'Second lesson description'],
                'lesson_type' => LessonType::LIVE,
                'sort_order' => 2,
                'estimated_minutes' => 60,
                'is_preview' => false,
                'is_active' => true,
                'published_at' => now(),
            ]
        );

        $exam1 = Exam::firstOrCreate(
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson1->id,
            ],
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson1->id,
                'title' => ['ar' => 'امتحان الدرس الأول', 'en' => 'Lesson One Exam'],
                'description' => ['ar' => 'وصف الامتحان', 'en' => 'Exam description'],
                'type' => 'mcq',
                'total_score' => 100,
                'duration_minutes' => 60,
                'is_active' => true,
            ]
        );

        $exam2 = Exam::firstOrCreate(
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson2->id,
            ],
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson2->id,
                'title' => ['ar' => 'امتحان الدرس الثاني', 'en' => 'Lesson Two Exam'],
                'description' => ['ar' => 'وصف الامتحان', 'en' => 'Exam description'],
                'type' => 'essay',
                'total_score' => 100,
                'duration_minutes' => 90,
                'is_active' => true,
            ]
        );

        $task1 = Task::firstOrCreate(
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson1->id,
            ],
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson1->id,
                'title' => ['ar' => 'مهمة الدرس الأول', 'en' => 'Lesson One Task'],
                'description' => ['ar' => 'وصف المهمة', 'en' => 'Task description'],
                'submission_type' => 'file',
                'max_score' => 100,
                'due_date' => now()->addDays(7),
                'is_active' => true,
            ]
        );

        $task2 = Task::firstOrCreate(
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson2->id,
            ],
            [
                'course_id' => $course->id,
                'lesson_id' => $lesson2->id,
                'title' => ['ar' => 'مهمة الدرس الثاني', 'en' => 'Lesson Two Task'],
                'description' => ['ar' => 'وصف المهمة', 'en' => 'Task description'],
                'submission_type' => 'text',
                'max_score' => 100,
                'due_date' => now()->addDays(14),
                'is_active' => true,
            ]
        );

        Enrollment::firstOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
            ],
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'user_id' => $user->id,
                'enrollment_mode' => EnrollmentMode::COURSE_FULL->value,
                'delivery_type' => 'online',
                'currency_code' => 'EGP',
                'total_amount' => 1000.000,
                'status' => EnrollmentStatus::ACTIVE->value,
                'pricing_type' => 'full',
                'enrolled_at' => now(),
                'branch_id' => $branch->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        );

        $this->command->info('Student complete data seeded successfully!');
        $this->command->info("Student: {$student->name} ({$student->email})");
        $this->command->info("Course: {$course->code}");
        $this->command->info("Lessons: 2");
        $this->command->info("Exams: 2");
        $this->command->info("Tasks: 2");
    }
}
