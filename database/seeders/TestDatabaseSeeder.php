<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class TestDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for testing.
     * Creates minimal test data for all models.
     */
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

        // 3) Create test user
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 4) Training & Enrollment seeders (minimal data)
        $this->call([
            TeacherSeeder::class,
            ProgramSeeder::class,
            CoursePriceSeeder::class,
            CourseSectionSeeder::class,
            TestLessonSeeder::class,
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
            TestJournalSeeder::class,
            TestJournalLineSeeder::class,
        ]);

        // 6) Media files (needed for lesson items)
        $this->call([
            MediaFileSeeder::class,
        ]);

        // 7) Training content seeders (test versions)
        $this->call([
            TestLessonItemSeeder::class,
            TestExamSeeder::class,
            TestExamQuestionSeeder::class,
            TaskSeeder::class,
            TaskSubmissionSeeder::class,
        ]);
    }
}

