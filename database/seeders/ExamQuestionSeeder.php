<?php

namespace Database\Seeders;

use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamQuestion;
use Illuminate\Database\Seeder;

class ExamQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exams = Exam::all();

        if ($exams->isEmpty()) {
            $this->command->warn('No exams found. Please seed exams first.');
            return;
        }

        foreach ($exams as $exam) {
            $questionsCount = rand(5, 10);
            
            for ($i = 1; $i <= $questionsCount; $i++) {
                $questionTypes = ['mcq', 'essay', 'true_false'];
                $type = $exam->type === 'mcq' ? 'mcq' : ($exam->type === 'essay' ? 'essay' : $questionTypes[array_rand($questionTypes)]);

                $questionData = [
                    'exam_id' => $exam->id,
                    'type' => $type,
                    'question' => [
                        'ar' => "سؤال {$i}",
                        'en' => "Question {$i}",
                    ],
                    'points' => rand(5, 20),
                    'order' => $i,
                ];

                // Add options and correct_answer for MCQ and true_false
                if ($type === 'mcq') {
                    $questionData['options'] = [
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
                    ];
                    $questionData['correct_answer'] = rand(0, 3);
                } elseif ($type === 'true_false') {
                    $questionData['options'] = [
                        'ar' => ['صحيح', 'خطأ'],
                        'en' => ['True', 'False'],
                    ];
                    $questionData['correct_answer'] = rand(0, 1);
                }

                ExamQuestion::create($questionData);
            }
        }

        $this->command->info('Exam questions seeded successfully!');
    }
}

