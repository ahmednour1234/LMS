<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        
        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Please seed branches first.');
            return;
        }

        $programs = [
            [
                'code' => 'PROG-001',
                'name' => [
                    'ar' => 'برنامج تطوير المهارات',
                    'en' => 'Skills Development Program',
                ],
                'description' => [
                    'ar' => 'برنامج شامل لتطوير المهارات المهنية والشخصية',
                    'en' => 'Comprehensive program for professional and personal skills development',
                ],
                'is_active' => true,
            ],
            [
                'code' => 'PROG-002',
                'name' => [
                    'ar' => 'برنامج الإدارة والقيادة',
                    'en' => 'Management and Leadership Program',
                ],
                'description' => [
                    'ar' => 'برنامج متخصص في الإدارة والقيادة التنفيذية',
                    'en' => 'Specialized program in management and executive leadership',
                ],
                'is_active' => true,
            ],
            [
                'code' => 'PROG-003',
                'name' => [
                    'ar' => 'برنامج تكنولوجيا المعلومات',
                    'en' => 'Information Technology Program',
                ],
                'description' => [
                    'ar' => 'برنامج شامل في تكنولوجيا المعلومات والبرمجة',
                    'en' => 'Comprehensive program in information technology and programming',
                ],
                'is_active' => true,
            ],
            [
                'code' => 'PROG-004',
                'name' => [
                    'ar' => 'برنامج التسويق الرقمي',
                    'en' => 'Digital Marketing Program',
                ],
                'description' => [
                    'ar' => 'برنامج متخصص في التسويق الرقمي والإعلانات',
                    'en' => 'Specialized program in digital marketing and advertising',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            foreach ($programs as $programData) {
                $programData['branch_id'] = $branch->id;
                $programData['code'] = $programData['code'] . '-' . $branch->id; // Make unique per branch
                
                Program::firstOrCreate(
                    [
                        'code' => $programData['code'],
                        'branch_id' => $branch->id,
                    ],
                    $programData
                );
            }
        }

        $this->command->info('Programs seeded successfully!');
    }
}
