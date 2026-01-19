<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Requests\Api\V1\Student\SubmitExamRequest;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamAttempt;
use App\Services\Student\ExamGradingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student Exams
 *
 * Student API for taking exams and viewing results.
 */
class ExamController extends ApiController
{
    public function __construct(
        private ExamGradingService $gradingService
    ) {}

    /**
     * List Exams
     *
     * Get all exams for a course with attempt status.
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Exams retrieved successfully.",
     *   "data": [...]
     * }
     */
    public function index(Request $request, int $course): JsonResponse
    {
        $student = auth('students')->user();
        $courseModel = $request->attributes->get('course');

        $exams = Exam::where('course_id', $course)
            ->where('is_active', true)
            ->withCount(['attempts' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->get();

        $examIds = $exams->pluck('id')->toArray();
        $latestAttempts = ExamAttempt::where('student_id', $student->id)
            ->whereIn('exam_id', $examIds)
            ->selectRaw('exam_id, MAX(submitted_at) as last_attempt_at, MAX(score) as best_score')
            ->groupBy('exam_id')
            ->get()
            ->keyBy('exam_id');

        $exams->transform(function ($exam) use ($latestAttempts) {
            $attempt = $latestAttempts->get($exam->id);
            $exam->setAttribute('has_attempt', $attempt !== null);
            $lastAttemptAt = $attempt?->last_attempt_at;
            $exam->setAttribute('last_attempt_at', $lastAttemptAt ? Carbon::parse($lastAttemptAt)->toISOString() : null);
            $exam->setAttribute('best_score', $attempt ? (float) $attempt->best_score : null);
            return $exam;
        });

        return $this->successResponse(
            \App\Http\Resources\V1\Student\ExamResource::collection($exams),
            'Exams retrieved successfully.'
        );
    }

    /**
     * Show Exam
     *
     * Get exam details (requires enrollment).
     *
     * @urlParam exam integer required The ID of the exam. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Exam retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "title": {...},
     *     "total_score": 100.00,
     *     "duration_minutes": 60
     *   }
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "You are not enrolled in this course.",
     *   "error": {
     *     "code": "FORBIDDEN"
     *   }
     * }
     */
    public function show(int $exam): JsonResponse
    {
        $student = auth('students')->user();
        $examModel = Exam::with(['course', 'questions' => function ($q) {
            $q->where('is_active', true)->orderBy('order');
        }])->find($exam);

        if (!$examModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Exam not found.',
                null,
                404
            );
        }

        $enrollment = \App\Domain\Enrollment\Models\Enrollment::where('student_id', $student->id)
            ->where('course_id', $examModel->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->first();

        if (!$enrollment) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'You are not enrolled in this course.',
                null,
                403
            );
        }

        $examModel->questions->transform(function ($q) {
            if ($q->type === 'mcq') {
                $q->makeVisible(['options']);
            }
            $q->makeHidden(['correct_answer']);
            return $q;
        });

        return $this->successResponse(
            new \App\Http\Resources\V1\Student\ExamResource($examModel),
            'Exam retrieved successfully.'
        );
    }

    public function start(int $exam): JsonResponse
    {
        $student = auth('students')->user();
        $examModel = Exam::with('course')->findOrFail($exam);

        $enrollment = \App\Domain\Enrollment\Models\Enrollment::where('student_id', $student->id)
            ->where('course_id', $examModel->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->first();

        if (!$enrollment) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'You are not enrolled in this course.',
                null,
                403
            );
        }

        $lastAttempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $examModel->id)
            ->orderBy('attempt_no', 'desc')
            ->first();

        $attemptNo = $lastAttempt ? ($lastAttempt->attempt_no + 1) : 1;

        $attempt = ExamAttempt::create([
            'exam_id' => $examModel->id,
            'student_id' => $student->id,
            'attempt_no' => $attemptNo,
            'status' => 'in_progress',
            'started_at' => now(),
            'max_score' => (float) $examModel->questions()->sum('points'),
        ]);

        return $this->createdResponse(
            new \App\Http\Resources\V1\Student\ExamResultResource($attempt),
            'Exam attempt started successfully.'
        );
    }

    /**
     * Get Exam Questions
     *
     * Get exam questions without correct answers (requires enrollment).
     *
     * @urlParam exam integer required The ID of the exam. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Exam questions retrieved successfully.",
     *   "data": [...]
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "You are not enrolled in this course.",
     *   "error": {
     *     "code": "FORBIDDEN"
     *   }
     * }
     */
    public function questions(int $exam): JsonResponse
    {
        $student = auth('students')->user();
        $examModel = Exam::with('course')->find($exam);

        if (!$examModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Exam not found.',
                null,
                404
            );
        }

        $enrollment = \App\Domain\Enrollment\Models\Enrollment::where('student_id', $student->id)
            ->where('course_id', $examModel->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->first();

        if (!$enrollment) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'You are not enrolled in this course.',
                null,
                403
            );
        }

        $questions = $examModel->questions()
            ->where('is_active', true)
            ->inRandomOrder()
            ->get()
            ->map(function ($q) {
                $q->makeHidden('correct_answer');
                return $q;
            });

        return $this->successResponse(
            \App\Http\Resources\V1\Student\ExamQuestionResource::collection($questions),
            'Exam questions retrieved successfully.'
        );
    }

    /**
     * Submit Exam
     *
     * Submit exam answers. Auto-grades MCQ and True/False questions.
     *
     * @urlParam exam integer required The ID of the exam. Example: 1
     * @bodyParam answers array required Array of answers. Example: [{"question_id": 1, "answer": "A"}]
     * @bodyParam answers.*.question_id integer required Question ID. Example: 1
     * @bodyParam answers.*.answer mixed required Answer (string, array, or boolean). Example: "A"
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Exam submitted successfully.",
     *   "data": {
     *     "id": 1,
     *     "score": 85.00,
     *     "max_score": 100.00,
     *     "percentage": 85.0,
     *     "status": "graded"
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "The provided data is invalid.",
     *   "error": {
     *     "code": "VALIDATION_ERROR"
     *   }
     * }
     */
    public function submit(SubmitExamRequest $request, int $exam): JsonResponse
    {
        $student = auth('students')->user();
        $examModel = Exam::with('course')->findOrFail($exam);

        $enrollment = \App\Domain\Enrollment\Models\Enrollment::where('student_id', $student->id)
            ->where('course_id', $examModel->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->first();

        if (!$enrollment) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'You are not enrolled in this course.',
                null,
                403
            );
        }

        $attempt = $this->gradingService->submitExam(
            $student,
            $examModel,
            $request->validated()['answers']
        );

        return $this->createdResponse(
            new \App\Http\Resources\V1\Student\ExamResultResource($attempt),
            'Exam submitted successfully.'
        );
    }

    /**
     * Get Exam Result
     *
     * Get latest exam attempt result with per-question feedback.
     *
     * @urlParam exam integer required The ID of the exam. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Exam result retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "score": 85.00,
     *     "max_score": 100.00,
     *     "percentage": 85.0,
     *     "answers": [...]
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "No exam attempt found.",
     *   "error": {
     *     "code": "NOT_FOUND"
     *   }
     * }
     */
    public function result(int $exam): JsonResponse
    {
        $student = auth('students')->user();
        $examModel = Exam::findOrFail($exam);

        $attempt = $this->gradingService->getLatestAttempt($student, $examModel);

        if (!$attempt) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'No exam attempt found.',
                null,
                404
            );
        }

        return $this->successResponse(
            new \App\Http\Resources\V1\Student\ExamResultResource($attempt),
            'Exam result retrieved successfully.'
        );
    }

    public function showAttempt(int $attempt): JsonResponse
    {
        $student = auth('students')->user();
        $attemptModel = ExamAttempt::with(['exam.course', 'answers.question'])->findOrFail($attempt);

        if ($attemptModel->student_id !== $student->id) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'You do not have access to this attempt.',
                null,
                403
            );
        }

        return $this->successResponse(
            new \App\Http\Resources\V1\Student\ExamResultResource($attemptModel),
            'Exam attempt retrieved successfully.'
        );
    }
}
