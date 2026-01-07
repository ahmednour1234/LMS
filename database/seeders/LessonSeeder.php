<?php

namespace Database\Seeders;

use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = CourseSection::all();

        if ($sections->isEmpty()) {
            $this->command->warn('No course sections found. Please seed course sections first.');
            return;
        }

        foreach ($sections as $section) {
            $lessonsPerSection = rand(2, 4);
            
            for ($i = 1; $i <= $lessonsPerSection; $i++) {
                $lessonTypes = [\App\Domain\Training\Enums\LessonType::RECORDED, \App\Domain\Training\Enums\LessonType::LIVE, \App\Domain\Training\Enums\LessonType::MIXED];
                $lessonType = $lessonTypes[array_rand($lessonTypes)];

                $lessonData = [
                    'section_id' => $section->id,
                    'title' => [
                        'ar' => "الدرس {$i}",
                        'en' => "Lesson {$i}",
                    ],
                    'description' => [
                        'ar' => "وصف الدرس {$i}",
                        'en' => "Description of lesson {$i}",
                    ],
                    'lesson_type' => $lessonType,
                    'sort_order' => $i,
                    'estimated_minutes' => rand(15, 90),
                    'is_preview' => $i === 1, // First lesson is preview
                    'is_active' => true,
                    'published_at' => now()->subDays(rand(0, 30)),
                ];

                Lesson::firstOrCreate(
                    [
                        'section_id' => $section->id,
                        'sort_order' => $i,
                    ],
                    $lessonData
                );
            }
        }

        $this->command->info('Lessons seeded successfully!');
    }
}

