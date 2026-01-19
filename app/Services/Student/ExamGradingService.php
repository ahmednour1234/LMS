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
    public function submitExam(Student $student, Exam $exam, array $answers): ExamAttempt
    {
        return DB::transaction(function () use ($student, $exam, $answers) {

            $questions = $exam->questions()->get()->keyBy('id'); // avoid N+1
            $maxScore  = (float) $questions->sum('points');

            $attemptNo = (int) (ExamAttempt::where('student_id', $student->id)
                    ->where('exam_id', $exam->id)
                    ->max('attempt_no') ?? 0) + 1;

            $attempt = ExamAttempt::create([
                'exam_id'      => $exam->id,
                'student_id'   => $student->id,
                'attempt_no'   => $attemptNo,
                'max_score'    => $maxScore,
                'started_at'   => now(),
                'submitted_at' => now(),
                'status'       => 'submitted',
            ]);

            $seenQuestionIds = [];

            foreach ($answers as $answerData) {
                $questionId = $answerData['question_id'] ?? null;
                if (!$questionId) {
                    continue;
                }

                // Prevent duplicate answers for same question in same submit payload
                if (isset($seenQuestionIds[$questionId])) {
                    continue;
                }
                $seenQuestionIds[$questionId] = true;

                /** @var ExamQuestion|null $question */
                $question = $questions->get($questionId);
                if (!$question) {
                    continue; // question not in this exam
                }

                $answerText     = $answerData['answer_text'] ?? null;
                $selectedOption = $answerData['selected_option'] ?? null;

                // Backward compatibility: "answer" key
                if ($answerText === null && array_key_exists('answer', $answerData)) {
                    $answerText = $answerData['answer'];
                }
                if ($selectedOption === null && array_key_exists('answer', $answerData)) {
                    $selectedOption = $answerData['answer'];
                }

                $pointsAwarded = 0;
                $isCorrect     = null;

                if ($question->type === 'mcq') {
                    $selected = $selectedOption ?? $answerText;

                    // Normalize selection to string
                    if (is_array($selected)) {
                        $selected = json_encode($selected, JSON_UNESCAPED_UNICODE);
                    }

                    $isCorrect = $this->checkAnswer($question, (string) $selected);
                    $pointsAwarded = $isCorrect ? (int) $question->points : 0;
                }

                ExamAnswer::updateOrCreate(
                    [
                        'attempt_id'  => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'answer'          => is_array($answerText) ? json_encode($answerText, JSON_UNESCAPED_UNICODE) : $answerText,
                        'answer_text'     => $question->type === 'essay' ? (is_array($answerText) ? json_encode($answerText, JSON_UNESCAPED_UNICODE) : $answerText) : null,
                        'selected_option' => $question->type === 'mcq' ? (is_array($selectedOption) ? json_encode($selectedOption, JSON_UNESCAPED_UNICODE) : $selectedOption) : null,
                        'points_awarded'  => $pointsAwarded,
                        'points_possible' => (float) $question->points,
                        'is_correct'      => $isCorrect,
                    ]
                );
            }

            // Auto grade MCQ (optional if you already graded inline, but keep for safety)
            $this->autoGradeMcq($attempt);

            // Compute score
            $this->computeAttemptScore($attempt);

            // Final status: if exam has only mcq -> graded automatically
            $finalStatus = $this->determineFinalStatus($exam, $attempt);

            if ($finalStatus === 'graded') {
                $attempt->update([
                    'status'    => 'graded',
                    'graded_at' => now(),
                ]);
            } else {
                // Ensure still submitted
                $attempt->update([
                    'status' => 'submitted',
                ]);
            }

            return $attempt->fresh(['answers.question']);
        });
    }


    public function autoGradeMcq(ExamAttempt $attempt): void
    {
        $attempt->load('answers.question');

        foreach ($attempt->answers as $answer) {
            if ($answer->question->type === 'mcq' && $answer->is_correct === null) {
                $isCorrect = $this->checkAnswer($answer->question, $answer->selected_option);
                $pointsAwarded = $isCorrect ? (int) $answer->question->points : 0;

                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_awarded' => $pointsAwarded,
                ]);
            }
        }
    }

    public function computeAttemptScore(ExamAttempt $attempt): int
    {
        $attempt->load('answers');
        $score = (int) $attempt->answers->sum('points_awarded');

        $attempt->update([
            'score' => $score,
            'percentage' => $attempt->max_score > 0 ? ($score / $attempt->max_score) * 100 : 0,
        ]);

        return $score;
    }

    public function determineFinalStatus(Exam $exam, ExamAttempt $attempt): string
    {
        if ($exam->type === 'mcq') {
            return 'graded';
        }

        $essayCount = $attempt->answers()
            ->whereHas('question', fn ($q) => $q->where('type', 'essay'))
            ->count();

        if ($essayCount === 0) {
            return 'graded';
        }

        $ungradedEssays = $attempt->answers()
            ->whereHas('question', fn ($q) => $q->where('type', 'essay'))
            ->whereNull('points_awarded')
            ->count();

        return $ungradedEssays === 0 ? 'graded' : 'submitted';
    }

    public function finalizeGrade(ExamAttempt $attempt, int $teacherId): void
    {
        DB::transaction(function () use ($attempt, $teacherId) {
            $this->computeAttemptScore($attempt);

            $attempt->update([
                'status' => 'graded',
                'graded_by_teacher_id' => $teacherId,
                'graded_at' => now(),
            ]);
        });
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
