<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Program;
use App\Domain\Training\Models\Teacher;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $programs = Program::all();
        $teachers = Teacher::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Please seed branches first.');
            return;
        }

        if ($programs->isEmpty()) {
            $this->command->warn('No programs found. Please seed programs first.');
            return;
        }

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Please seed teachers first.');
            return;
        }

        $courses = [
            [
                'code' => 'COURSE-001',
                'name' => [
                    'ar' => 'مقدمة في البرمجة',
                    'en' => 'Introduction to Programming',
                ],
                'description' => [
                    'ar' => 'دورة شاملة لتعلم أساسيات البرمجة',
                    'en' => 'Comprehensive course for learning programming fundamentals',
                ],
                'delivery_type' => DeliveryType::Online->value,
                'duration_hours' => 40,
            ],
            [
                'code' => 'COURSE-002',
                'name' => [
                    'ar' => 'إدارة المشاريع',
                    'en' => 'Project Management',
                ],
                'description' => [
                    'ar' => 'تعلم مهارات إدارة المشاريع بكفاءة',
                    'en' => 'Learn efficient project management skills',
                ],
                'delivery_type' => DeliveryType::Onsite->value,
                'duration_hours' => 30,
            ],
            [
                'code' => 'COURSE-003',
                'name' => [
                    'ar' => 'التسويق الإلكتروني',
                    'en' => 'Digital Marketing',
                ],
                'description' => [
                    'ar' => 'استراتيجيات التسويق الرقمي الحديثة',
                    'en' => 'Modern digital marketing strategies',
                ],
                'delivery_type' => DeliveryType::Virtual->value,
                'duration_hours' => 35,
            ],
            [
                'code' => 'COURSE-004',
                'name' => [
                    'ar' => 'القيادة والإدارة',
                    'en' => 'Leadership and Management',
                ],
                'description' => [
                    'ar' => 'تطوير مهارات القيادة والإدارة',
                    'en' => 'Developing leadership and management skills',
                ],
                'delivery_type' => DeliveryType::Onsite->value,
                'duration_hours' => 25,
            ],
            [
                'code' => 'COURSE-005',
                'name' => [
                    'ar' => 'تحليل البيانات',
                    'en' => 'Data Analysis',
                ],
                'description' => [
                    'ar' => 'تعلم تحليل البيانات والبيانات الضخمة',
                    'en' => 'Learn data analysis and big data',
                ],
                'delivery_type' => DeliveryType::Online->value,
                'duration_hours' => 45,
            ],
        ];

        foreach ($branches as $branch) {
            foreach ($programs->where('branch_id', $branch->id) as $program) {
                foreach ($courses as $index => $courseData) {
                    $courseData['branch_id'] = $branch->id;
                    $courseData['program_id'] = $program->id;
                    $courseData['code'] = $courseData['code'] . '-' . $branch->id . '-' . $program->id;
                    
                    // Assign random teacher as owner
                    $courseData['owner_teacher_id'] = $teachers->random()->id;
                    $courseData['is_active'] = true;

                    $course = Course::firstOrCreate(
                        [
                            'code' => $courseData['code'],
                            'branch_id' => $branch->id,
                        ],
                        $courseData
                    );

                    // Attach 1-2 additional teachers randomly
                    if ($teachers->count() > 1) {
                        $additionalTeachers = $teachers->where('id', '!=', $course->owner_teacher_id)
                            ->random(rand(1, min(2, $teachers->count() - 1)));
                        $course->teachers()->syncWithoutDetaching($additionalTeachers->pluck('id')->toArray());
                    }
                }
            }
        }

        $this->command->info('Courses seeded successfully!');
    }
}
