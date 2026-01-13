<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreLessonRequest;
use App\Http\Requests\Teacher\UpdateLessonRequest;
use App\Http\Resources\Public\LessonResource;
use App\Http\Services\LessonService;
use App\Http\Services\TeacherOwnershipGuardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher - Lessons
 */
class LessonController extends ApiController
{
    public function __construct(
        protected LessonService $service,
        protected TeacherOwnershipGuardService $guard
    ) {}

    /**
     * List lessons
     * @queryParam section_id int Example: 5
     * @queryParam active boolean Example: 1
     * @queryParam is_preview boolean Example: 0
     * @queryParam lesson_type string recorded|live|mixed Example: recorded
     * @queryParam q string Search title Example: intro
     * @queryParam sort string sort_order|newest|oldest Example: sort_order
     * @queryParam per_page int Example: 15
     */
    public function index(): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $filters = request()->only(['section_id','active','is_preview','lesson_type','q','sort']);
        $perPage = (int) request('per_page', 15);

        if (!empty($filters['section_id'])) {
            $section = CourseSection::findOrFail((int) $filters['section_id']);
            $this->guard->assertSectionOwner($teacherId, $section);
        }

        $data = $this->service->paginate($filters, $perPage);

        return $this->successResponse(
            LessonResource::collection($data)->response()->getData(true),
            'OK'
        );
    }

    public function store(StoreLessonRequest $request): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $data = $request->validated();

        $section = CourseSection::findOrFail((int) $data['section_id']);
        $this->guard->assertSectionOwner($teacherId, $section);

        $lesson = $this->service->create($data);

        return $this->successResponse(new LessonResource($lesson), 'Created', 201);
    }

    public function show(Lesson $lesson): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertLessonOwner($teacherId, $lesson);

        $lesson->loadCount('items');

        return $this->successResponse(new LessonResource($lesson), 'OK');
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertLessonOwner($teacherId, $lesson);

        $updated = $this->service->update($lesson, $request->validated());

        return $this->successResponse(new LessonResource($updated), 'Updated');
    }

    public function toggleActive(Lesson $lesson): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertLessonOwner($teacherId, $lesson);

        $active = request()->has('is_active') ? (bool) request('is_active') : null;
        $updated = $this->service->toggleActive($lesson, $active);

        return $this->successResponse(new LessonResource($updated), 'OK');
    }
}
