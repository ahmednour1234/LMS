<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSection;
use Illuminate\Database\Seeder;

class CourseSectionSeeder extends Seeder
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
            $sections = [
                [
                    'title' => [
                        'ar' => 'مقدمة',
                        'en' => 'Introduction',
                    ],
                    'description' => [
                        'ar' => 'مقدمة عامة للدورة',
                        'en' => 'General introduction to the course',
                    ],
                    'order' => 1,
                    'is_active' => true,
                ],
                [
                    'title' => [
                        'ar' => 'الأساسيات',
                        'en' => 'Fundamentals',
                    ],
                    'description' => [
                        'ar' => 'المفاهيم والأساسيات الأساسية',
                        'en' => 'Core concepts and fundamentals',
                    ],
                    'order' => 2,
                    'is_active' => true,
                ],
                [
                    'title' => [
                        'ar' => 'المفاهيم المتقدمة',
                        'en' => 'Advanced Concepts',
                    ],
                    'description' => [
                        'ar' => 'مفاهيم متقدمة وتطبيقات عملية',
                        'en' => 'Advanced concepts and practical applications',
                    ],
                    'order' => 3,
                    'is_active' => true,
                ],
                [
                    'title' => [
                        'ar' => 'التطبيق العملي',
                        'en' => 'Practical Application',
                    ],
                    'description' => [
                        'ar' => 'مشاريع وتطبيقات عملية',
                        'en' => 'Projects and practical applications',
                    ],
                    'order' => 4,
                    'is_active' => true,
                ],
            ];

            foreach ($sections as $sectionData) {
                CourseSection::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'order' => $sectionData['order'],
                    ],
                    [
                        'course_id' => $course->id,
                        'title' => $sectionData['title'],
                        'description' => $sectionData['description'],
                        'order' => $sectionData['order'],
                        'is_active' => $sectionData['is_active'],
                    ]
                );
            }
        }

        $this->command->info('Course sections seeded successfully!');
    }
}

