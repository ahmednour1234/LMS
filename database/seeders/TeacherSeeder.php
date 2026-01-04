<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'Dr. Ahmed Mohamed',
                'email' => 'ahmed.teacher@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'photo' => null,
                'active' => true,
            ],
            [
                'name' => 'Prof. Sara Ali',
                'email' => 'sara.teacher@example.com',
                'password' => Hash::make('password'),
                'sex' => 'female',
                'photo' => null,
                'active' => true,
            ],
            [
                'name' => 'Dr. Omar Hassan',
                'email' => 'omar.teacher@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'photo' => null,
                'active' => true,
            ],
            [
                'name' => 'Ms. Fatima Ibrahim',
                'email' => 'fatima.teacher@example.com',
                'password' => Hash::make('password'),
                'sex' => 'female',
                'photo' => null,
                'active' => true,
            ],
            [
                'name' => 'Dr. Khalid Mansour',
                'email' => 'khalid.teacher@example.com',
                'password' => Hash::make('password'),
                'sex' => 'male',
                'photo' => null,
                'active' => true,
            ],
        ];

        foreach ($teachers as $teacher) {
            Teacher::firstOrCreate(
                ['email' => $teacher['email']],
                $teacher
            );
        }

        $this->command->info('Teachers seeded successfully!');
    }
}
