<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\LessonItem;
use Illuminate\Database\Seeder;

class LessonItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lessons = Lesson::all();

        if ($lessons->isEmpty()) {
            $this->command->warn('No lessons found. Please seed lessons first.');
            return;
        }

        foreach ($lessons as $lesson) {
            $itemsPerLesson = rand(2, 5);
            
            for ($i = 1; $i <= $itemsPerLesson; $i++) {
                $itemTypes = ['video', 'pdf', 'file', 'link'];
                $type = $itemTypes[array_rand($itemTypes)];

                $itemData = [
                    'lesson_id' => $lesson->id,
                    'type' => $type,
                    'title' => [
                        'ar' => "عنصر {$i}",
                        'en' => "Item {$i}",
                    ],
                    'order' => $i,
                    'is_active' => true,
                ];

                // Add external_url for link type
                if ($type === 'link') {
                    $itemData['external_url'] = 'https://example.com/resource-' . $i;
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

        $this->command->info('Lesson items seeded successfully!');
    }
}

