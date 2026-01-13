<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamQuestion;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreExamQuestionRequest;
use App\Http\Requests\Teacher\UpdateExamQuestionRequest;
use App\Http\Resources\Public\ExamQuestionResource;
use App\Http\Services\ExamQuestionService;
use App\Http\Services\TeacherOwnershipGuardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher - Exam Questions
 */
class ExamQuestionController extends ApiController
{
    public function __construct(
        protected ExamQuestionService $service,
        protected TeacherOwnershipGuardService $guard
    ) {}

    /**
     * List questions
     * @queryParam exam_id int required Example: 3
     * @queryParam type string mcq|essay Example: mcq
     * @queryParam active boolean Example: 1
     * @queryParam per_page int Example: 50
     */
    public function index(): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();

        $filters = request()->only(['exam_id','type','active','sort']);
        if (empty($filters['exam_id'])) {
            return $this->errorResponse(\App\Http\Enums\ApiErrorCode::VALIDATION_ERROR, 'exam_id is required', null, 422);
        }

        $exam = Exam::findOrFail((int) $filters['exam_id']);
        $this->guard->assertExamOwner($teacherId, $exam);

        $perPage = (int) request('per_page', 50);
        $data = $this->service->paginate($filters, $perPage);

        return $this->successResponse(
            ExamQuestionResource::collection($data)->response()->getData(true),
            'OK'
        );
    }

    /**
     * Store question(s)
     * - single: exam_id + fields
     * - bulk: questions[] (UI repeater)
     */
    public function store(StoreExamQuestionRequest $request): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $data = $request->validated();

        // bulk
        if (!empty($data['questions'])) {
            $examId = (int) ($data['exam_id'] ?? ($data['questions'][0]['exam_id'] ?? 0));
            $exam = Exam::findOrFail($examId);
            $this->guard->assertExamOwner($teacherId, $exam);

            $created = $this->service->bulkCreate($examId, $data['questions']);

            return $this->successResponse(
                ExamQuestionResource::collection(collect($created)),
                'Created',
                201
            );
        }

        // single
        $exam = Exam::findOrFail((int) $data['exam_id']);
        $this->guard->assertExamOwner($teacherId, $exam);

        $question = $this->service->create($data);

        return $this->successResponse(new ExamQuestionResource($question), 'Created', 201);
    }

    public function update(UpdateExamQuestionRequest $request, ExamQuestion $question): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertExamQuestionOwner($teacherId, $question);

        $updated = $this->service->update($question, $request->validated());

        return $this->successResponse(new ExamQuestionResource($updated), 'Updated');
    }

    public function destroy(ExamQuestion $question): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertExamQuestionOwner($teacherId, $question);

        $this->service->delete($question);

        return $this->successResponse(null, 'Deleted');
    }
}
