<?php

namespace App\Services\Student;

use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamAnswer;
use App\Domain\Training\Models\ExamAttempt;
use App\Domain\Training\Models\ExamQuestion;
use App\Exceptions\BusinessException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamGradingService
{
    public function submitExam(
        Student $student,
        Exam $exam,
        array $answers
    ): ExamAttempt {
        $attempt = DB::transaction(function () use ($student, $exam, $answers) {
            $maxScore = (float) $exam->questions()->sum('points');

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'max_score' => $maxScore,
                'started_at' => now(),
                'status' => 'completed',
                'submitted_at' => now(),
            ]);

            $score = 0;
            $autoGradedCount = 0;

            foreach ($answers as $answerData) {
                $questionId = $answerData['question_id'] ?? null;
                $answerText = $answerData['answer'] ?? null;

                if (!$questionId) {
                    continue;
                }

                $question = ExamQuestion::where('exam_id', $exam->id)
                    ->where('id', $questionId)
                    ->first();

                if (!$question) {
                    continue;
                }

                $pointsEarned = 0;
                $isCorrect = null;
                $feedback = null;

                if (in_array($question->type, ['mcq', 'true_false'])) {
                    $isCorrect = $this->checkAnswer($question, $answerText);
                    $pointsEarned = $isCorrect ? (float) $question->points : 0;
                    $autoGradedCount++;
                }

                ExamAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                    'answer' => is_array($answerText) ? json_encode($answerText) : $answerText,
                    'points_earned' => $pointsEarned,
                    'points_possible' => (float) $question->points,
                    'is_correct' => $isCorrect,
                    'feedback' => $feedback,
                ]);

                $score += $pointsEarned;
            }

            $percentage = $maxScore > 0 ? ($score / $maxScore) * 100 : 0;

            $attempt->update([
                'score' => $score,
                'percentage' => $percentage,
                'status' => $autoGradedCount === count($answers) ? 'graded' : 'completed',
                'graded_at' => $autoGradedCount === count($answers) ? now() : null,
            ]);

            return $attempt->fresh(['answers.question']);
        });

        return $attempt;
    }

    private function checkAnswer(ExamQuestion $question, mixed $studentAnswer): bool
    {
        $correctAnswer = $question->correct_answer;

        if ($question->type === 'true_false') {
            return strtolower(trim((string) $studentAnswer)) === strtolower(trim((string) $correctAnswer));
        }

        if ($question->type === 'mcq') {
            if (is_array($studentAnswer)) {
                return $this->arraysEqual($studentAnswer, (array) $correctAnswer);
            }
            return (string) $studentAnswer === (string) $correctAnswer;
        }

        return false;
    }

    private function arraysEqual(array $a, array $b): bool
    {
        sort($a);
        sort($b);
        return $a === $b;
    }

    public function getLatestAttempt(Student $student, Exam $exam): ?ExamAttempt
    {
        return ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->with(['answers.question'])
            ->latest('submitted_at')
            ->first();
    }
}
