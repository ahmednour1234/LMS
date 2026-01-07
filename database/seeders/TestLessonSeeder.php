<?php

namespace Database\Seeders;

use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Enums\LessonType;
use Illuminate\Database\Seeder;

class TestLessonSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     * Creates minimal lessons for testing purposes.
     */
    public function run(): void
    {
        $sections = CourseSection::take(3)->get();

        if ($sections->isEmpty()) {
            $this->command->warn('No course sections found. Please seed course sections first.');
            return;
        }

        foreach ($sections as $index => $section) {
            // Create exactly 2 lessons per section for predictable testing
            for ($i = 1; $i <= 2; $i++) {
                $lessonData = [
                    'section_id' => $section->id,
                    'title' => [
                        'ar' => "درس تجريبي {$i}",
                        'en' => "Test Lesson {$i}",
                    ],
                    'description' => [
                        'ar' => "وصف تجريبي للدرس {$i}",
                        'en' => "Test description for lesson {$i}",
                    ],
                    'lesson_type' => $i === 1 ? LessonType::RECORDED : LessonType::LIVE,
                    'sort_order' => $i,
                    'estimated_minutes' => 30 * $i,
                    'is_preview' => $i === 1,
                    'is_active' => true,
                    'published_at' => now(),
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

        $this->command->info('Test lessons seeded successfully!');
    }
}

