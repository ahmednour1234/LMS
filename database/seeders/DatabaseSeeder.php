<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1) Roles & Permissions
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // 2) Branches
        $this->call([
            BranchSeeder::class,
        ]);

        // 3) Create/Update test user (no duplicates)
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                // لو مش عايز تغير باسورد كل مرة، سيبه ثابت
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 4) Training & Enrollment seeders (order matters due to dependencies)
        $this->call([
            TeacherSeeder::class,
            ProgramSeeder::class,
            CourseSeeder::class,
            CoursePriceSeeder::class,
            CourseSectionSeeder::class,
            LessonSeeder::class,
            StudentSeeder::class,
            EnrollmentSeeder::class,
        ]);

        // 5) Accounting seeders (needs Accounts and CostCenters first)
        $this->call([
            CategorySeeder::class,
            PaymentMethodSeeder::class,
            CostCenterSeeder::class,
            SettingSeeder::class,
            AccountingAccountSeeder::class,
            JournalSeeder::class,
            JournalLineSeeder::class,
        ]);

        // 6) Media files (needed for lesson items and task submissions)
        $this->call([
            MediaFileSeeder::class,
        ]);

        // 7) Training content seeders (depends on courses, lessons, students)
        $this->call([
            LessonItemSeeder::class,
            ExamSeeder::class,
            ExamQuestionSeeder::class,
            TaskSeeder::class,
            TaskSubmissionSeeder::class,
        ]);
    }
}
