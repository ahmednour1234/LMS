<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\LessonItem;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreLessonItemRequest;
use App\Http\Requests\Teacher\UpdateLessonItemRequest;
use App\Http\Resources\Api\V1\Public\LessonItemResource;
use App\Http\Services\LessonItemService;
use App\Http\Services\TeacherOwnershipGuardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher - Lesson Items
 */
class LessonItemController extends ApiController
{
    public function __construct(
        protected LessonItemService $service,
        protected TeacherOwnershipGuardService $guard
    ) {}

    /**
     * List lesson items
     * @queryParam lesson_id int Example: 12
     * @queryParam type string video|pdf|file|link Example: video
     * @queryParam active boolean Example: 1
     * @queryParam sort string order|newest|oldest Example: order
     * @queryParam per_page int Example: 15
     */
    public function index(): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $filters = request()->only(['lesson_id','type','active','sort']);
        $perPage = (int) request('per_page', 15);

        if (!empty($filters['lesson_id'])) {
            $lesson = Lesson::findOrFail((int) $filters['lesson_id']);
            $this->guard->assertLessonOwner($teacherId, $lesson);
        }

        $data = $this->service->paginate($filters, $perPage);

        return $this->successResponse(
            LessonItemResource::collection($data)->response()->getData(true),
            'OK'
        );
    }

    public function store(StoreLessonItemRequest $request): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $data = $request->validated();

        $lesson = Lesson::findOrFail((int) $data['lesson_id']);
        $this->guard->assertLessonOwner($teacherId, $lesson);

        $item = $this->service->create($data);

        return $this->successResponse(new LessonItemResource($item->load('mediaFile')), 'Created', 201);
    }

    public function show(LessonItem $item): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertLessonItemOwner($teacherId, $item);

        return $this->successResponse(new LessonItemResource($item->load('mediaFile')), 'OK');
    }

    public function update(UpdateLessonItemRequest $request, LessonItem $item): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertLessonItemOwner($teacherId, $item);

        $updated = $this->service->update($item, $request->validated());

        return $this->successResponse(new LessonItemResource($updated->load('mediaFile')), 'Updated');
    }

    public function toggleActive(LessonItem $item): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $this->guard->assertLessonItemOwner($teacherId, $item);

        $active = request()->has('is_active') ? (bool) request('is_active') : null;
        $updated = $this->service->toggleActive($item, $active);

        return $this->successResponse(new LessonItemResource($updated->load('mediaFile')), 'OK');
    }
}
