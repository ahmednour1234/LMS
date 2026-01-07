<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\LessonItem;
use Illuminate\Database\Seeder;

class TestLessonItemSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     * Creates minimal lesson items for testing purposes.
     */
    public function run(): void
    {
        $lessons = Lesson::take(5)->get();

        if ($lessons->isEmpty()) {
            $this->command->warn('No lessons found. Please seed lessons first.');
            return;
        }

        foreach ($lessons as $lesson) {
            // Create exactly 2 items per lesson for predictable testing
            // Valid types: 'video', 'pdf', 'file', 'link'
            for ($i = 1; $i <= 2; $i++) {
                $itemType = $i === 1 ? 'video' : 'pdf';
                
                $itemData = [
                    'lesson_id' => $lesson->id,
                    'type' => $itemType,
                    'title' => [
                        'ar' => "عنصر تجريبي {$i}",
                        'en' => "Test Item {$i}",
                    ],
                    'order' => $i,
                    'is_active' => true,
                ];

                // Add external_url for link type
                if ($itemType === 'link') {
                    $itemData['external_url'] = 'https://example.com/test-resource-' . $i;
                }

                LessonItem::firstOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                        'order' => $i,
                    ],
                    $itemData
                );
            }
        }

        $this->command->info('Test lesson items seeded successfully!');
    }
}

