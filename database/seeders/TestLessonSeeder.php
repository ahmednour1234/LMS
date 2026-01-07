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
        // The foreign key references 'sections' table, so query that directly
        // Check which table actually exists and has data
        $sectionsTable = \Illuminate\Support\Facades\DB::table('sections')->limit(3)->get();
        
        // If 'sections' is empty, try 'course_sections'
        if ($sectionsTable->isEmpty()) {
            $sectionsTable = \Illuminate\Support\Facades\DB::table('course_sections')->limit(3)->get();
        }

        if ($sectionsTable->isEmpty()) {
            $this->command->warn('No course sections found. Please seed course sections first.');
            return;
        }

        $createdCount = 0;
        foreach ($sectionsTable as $section) {
            $sectionId = $section->id;

            // Create exactly 2 lessons per section for predictable testing
            for ($i = 1; $i <= 2; $i++) {
                // Check if lesson already exists
                $existingLesson = Lesson::where('section_id', $sectionId)
                    ->where('sort_order', $i)
                    ->first();

                if ($existingLesson) {
                    continue; // Skip if already exists
                }

                try {
                    $lessonData = [
                        'section_id' => $sectionId,
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

                    Lesson::create($lessonData);
                    $createdCount++;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (str_contains($e->getMessage(), 'foreign key constraint')) {
                        $this->command->warn("Cannot create lesson for section {$sectionId}: Foreign key constraint failed. Section may not exist.");
                    } else {
                        throw $e;
                    }
                }
            }
        }

        if ($createdCount > 0) {
            $this->command->info("Test lessons seeded successfully! Created {$createdCount} lessons.");
        } else {
            $this->command->warn('No lessons were created. All may already exist or sections are invalid.');
        }
    }
}

