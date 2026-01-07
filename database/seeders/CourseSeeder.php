<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Program;
use App\Domain\Training\Models\Teacher;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::query()->get();
        $programs = Program::query()->get();
        $teachers = Teacher::query()->get();

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
                'name' => ['ar' => 'مقدمة في البرمجة', 'en' => 'Introduction to Programming'],
                'description' => ['ar' => 'دورة شاملة لتعلم أساسيات البرمجة', 'en' => 'Comprehensive course for learning programming fundamentals'],
                'delivery_type' => 'online',
                'duration_hours' => 40,
            ],
            [
                'code' => 'COURSE-002',
                'name' => ['ar' => 'إدارة المشاريع', 'en' => 'Project Management'],
                'description' => ['ar' => 'تعلم مهارات إدارة المشاريع بكفاءة', 'en' => 'Learn efficient project management skills'],
                'delivery_type' => 'onsite',
                'duration_hours' => 30,
            ],
            [
                'code' => 'COURSE-003',
                'name' => ['ar' => 'التسويق الإلكتروني', 'en' => 'Digital Marketing'],
                'description' => ['ar' => 'استراتيجيات التسويق الرقمي الحديثة', 'en' => 'Modern digital marketing strategies'],
                'delivery_type' => 'online',
                'duration_hours' => 35,
            ],
            [
                'code' => 'COURSE-004',
                'name' => ['ar' => 'القيادة والإدارة', 'en' => 'Leadership and Management'],
                'description' => ['ar' => 'تطوير مهارات القيادة والإدارة', 'en' => 'Developing leadership and management skills'],
                'delivery_type' => 'onsite',
                'duration_hours' => 25,
            ],
            [
                'code' => 'COURSE-005',
                'name' => ['ar' => 'تحليل البيانات', 'en' => 'Data Analysis'],
                'description' => ['ar' => 'تعلم تحليل البيانات والبيانات الضخمة', 'en' => 'Learn data analysis and big data'],
                'delivery_type' => 'online',
                'duration_hours' => 45,
            ],
        ];

        foreach ($branches as $branch) {

            $branchPrograms = $programs->where('branch_id', $branch->id);

            foreach ($branchPrograms as $program) {

                foreach ($courses as $courseData) {

                    $data = $courseData;

                    $data['branch_id'] = $branch->id;
                    $data['program_id'] = $program->id;

                    // code unique per branch & program
                    $data['code'] = $courseData['code'] . '-' . $branch->id . '-' . $program->id;

                    // owner teacher
                    $ownerTeacher = $teachers->random();
                    $data['owner_teacher_id'] = $ownerTeacher->id;

                    $data['is_active'] = true;

                    $course = Course::updateOrCreate(
                        ['code' => $data['code'], 'branch_id' => $branch->id],
                        $data
                    );

                    // attach extra teachers (excluding owner)
                    if ($teachers->count() > 1) {
                        $maxExtra = min(2, $teachers->count() - 1);
                        $extraCount = $maxExtra >= 2 ? rand(1, 2) : 1;

                        $extraTeachers = $teachers
                            ->where('id', '!=', $course->owner_teacher_id)
                            ->shuffle()
                            ->take($extraCount)
                            ->pluck('id')
                            ->values()
                            ->all();

                        if (!empty($extraTeachers)) {
                            $course->teachers()->syncWithoutDetaching($extraTeachers);
                        }
                    }
                }
            }
        }

        $this->command->info('Courses seeded successfully!');
    }
}
