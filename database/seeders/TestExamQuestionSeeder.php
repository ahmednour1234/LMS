<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamQuestion;
use Illuminate\Database\Seeder;

class TestExamQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     * Creates minimal exam questions for testing purposes.
     */
    public function run(): void
    {
        $exams = Exam::take(3)->get();

        if ($exams->isEmpty()) {
            $this->command->warn('No exams found. Please seed exams first.');
            return;
        }

        foreach ($exams as $exam) {
            // Create exactly 3 questions per exam for predictable testing
            for ($i = 1; $i <= 3; $i++) {
                $type = 'mcq'; // Use MCQ for all test questions
                
                $questionData = [
                    'exam_id' => $exam->id,
                    'type' => $type,
                    'question' => [
                        'ar' => "سؤال تجريبي {$i}",
                        'en' => "Test Question {$i}",
                    ],
                    'points' => 10 * $i,
                    'order' => $i,
                    'options' => [
                        'ar' => [
                            'الخيار أ',
                            'الخيار ب',
                            'الخيار ج',
                            'الخيار د',
                        ],
                        'en' => [
                            'Option A',
                            'Option B',
                            'Option C',
                            'Option D',
                        ],
                    ],
                    'correct_answer' => 0, // First option is always correct in test
                ];

                ExamQuestion::firstOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'order' => $i,
                    ],
                    $questionData
                );
            }
        }

        $this->command->info('Test exam questions seeded successfully!');
    }
}

