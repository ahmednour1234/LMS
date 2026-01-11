<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::where('is_active', true)->get();

        if ($branches->isEmpty()) {
            $this->command->warn('No active branches found. Please seed branches first.');
            return;
        }

        $students = [
            [
                'name' => 'Mohamed Ali',
                'student_code' => 'STU-001',
                'national_id' => '12345678901234',
                'phone' => '01001234567',
                'email' => 'mohamed.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'status' => 'active',
            ],
            [
                'name' => 'Fatima Hassan',
                'student_code' => 'STU-002',
                'national_id' => '23456789012345',
                'phone' => '01002345678',
                'email' => 'fatima.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'female',
                'status' => 'active',
            ],
            [
                'name' => 'Ahmed Ibrahim',
                'student_code' => 'STU-003',
                'national_id' => '34567890123456',
                'phone' => '01003456789',
                'email' => 'ahmed.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'status' => 'active',
            ],
            [
                'name' => 'Sara Mohamed',
                'student_code' => 'STU-004',
                'national_id' => '45678901234567',
                'phone' => '01004567890',
                'email' => 'sara.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'female',
                'status' => 'active',
            ],
            [
                'name' => 'Omar Khalid',
                'student_code' => 'STU-005',
                'national_id' => '56789012345678',
                'phone' => '01005678901',
                'email' => 'omar.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'status' => 'inactive',
            ],
            [
                'name' => 'Layla Nour',
                'student_code' => 'STU-006',
                'national_id' => '67890123456789',
                'phone' => '01006789012',
                'email' => 'layla.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'female',
                'status' => 'active',
            ],
            [
                'name' => 'Khaled Mostafa',
                'student_code' => 'STU-007',
                'national_id' => '78901234567890',
                'phone' => '01007890123',
                'email' => 'khaled.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'status' => 'inactive',
            ],
            [
                'name' => 'Nour Hamed',
                'student_code' => 'STU-008',
                'national_id' => '89012345678901',
                'phone' => '01008901234',
                'email' => 'nour.student@example.com',
                'password' => Hash::make('password'),
                'sex' => 'female',
                'status' => 'active',
            ],
        ];

        $branchIndex = 0;
        foreach ($students as $studentData) {
            $branch = $branches[$branchIndex % $branches->count()];
            
            // Make student_code unique per branch
            $studentCode = $studentData['student_code'] . '-' . $branch->id;

            // Create student record
            Student::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'branch_id' => $branch->id,
                    'name' => $studentData['name'],
                    'student_code' => $studentCode,
                    'national_id' => $studentData['national_id'],
                    'phone' => $studentData['phone'],
                    'email' => $studentData['email'],
                    'password' => $studentData['password'],
                    'sex' => $studentData['sex'],
                    'status' => $studentData['status'],
                    'email_verified_at' => now(),
                ]
            );

            $branchIndex++;
        }

        $this->command->info('Students seeded successfully!');
    }
}
