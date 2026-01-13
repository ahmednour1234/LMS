<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreExamRequest;
use App\Http\Requests\Teacher\UpdateExamRequest;
use App\Http\Resources\Api\V1\Public\ExamResource;
use App\Http\Services\ExamService;
use App\Http\Services\TeacherOwnershipGuardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher - Exams
 */
class ExamController extends ApiController
{
    public function __construct(
        protected ExamService $service,
        protected TeacherOwnershipGuardService $guard
    ) {}

    /**
     * List exams
     * @queryParam course_id int Example: 10
     * @queryParam lesson_id int Example: 77
     * @queryParam type string mcq|essay|mixed Example: mcq
     * @queryParam active boolean Example: 1
     * @queryParam q string Search title Example: exam
     * @queryParam per_page int Example: 15
     */
    public function index(): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $filters = request()->only(['course_id','lesson_id','type','active','q','sort']);
        $perPage = (int) request('per_page', 15);

        if (!empty($filters['course_id'])) {
            $course = Course::findOrFail((int) $filters['course_id']);
            $this->guard->assertCourseOwner($teacherId, $course);
        }

        $data = $this->service->paginate($filters, $perPage);

        return $this->successResponse(
            ExamResource::collection($data)->response()->getData(true),
            'OK'
        );
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $data = $request->validated();

        $course = Course::findOrFail((int) $data['course_id']);
        $this->guard->assertCourseOwner($teacherId, $course);

        $exam = $this->service->create($data);

        return $this->successResponse(new ExamResource($exam), 'Created', 201);
    }

    public function show(Exam $exam): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertExamOwner($teacherId, $exam);

        $exam->loadCount('questions');

        return $this->successResponse(new ExamResource($exam), 'OK');
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertExamOwner($teacherId, $exam);

        $updated = $this->service->update($exam, $request->validated());

        return $this->successResponse(new ExamResource($updated), 'Updated');
    }

    public function toggleActive(Exam $exam): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertExamOwner($teacherId, $exam);

        $active = request()->has('is_active') ? (bool) request('is_active') : null;
        $updated = $this->service->toggleActive($exam, $active);

        return $this->successResponse(new ExamResource($updated), 'OK');
    }
}
