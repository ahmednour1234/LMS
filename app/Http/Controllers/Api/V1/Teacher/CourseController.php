<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Services\CoursePriceService;
use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Requests\Teacher\StoreCourseRequest;
use App\Http\Requests\Teacher\UpdateCourseRequest;
use App\Http\Resources\Api\V1\Public\CourseResource;
use App\Http\Services\TeacherCourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @group Teacher Courses
 *
 * APIs for teachers to manage their own courses (with pricing).
 */
class CourseController extends ApiController
{
    public function __construct(
        protected TeacherCourseService $teacherCourseService,
        protected CoursePriceService $coursePriceService,
    ) {}

    /**
     * My Courses (Paginated)
     *
     * @queryParam q string Search by code or name(ar/en). Example: Laravel
     * @queryParam active boolean Filter by active (default 1). Example: 1
     * @queryParam program_id int Filter by program (must belong to teacher). Example: 2
     * @queryParam delivery_type string Filter by delivery type (onsite, online, hybrid). Example: online
     * @queryParam sort string Sort (newest, oldest, name). Example: newest
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $filters = request()->only(['q', 'active', 'program_id', 'delivery_type', 'sort']);
        $perPage = (int) request()->get('per_page', 15);

        // ✅ secure filtering: program_id must belong to teacher (handled in service)
        $courses = $this->teacherCourseService->myCourses($teacher->id, $filters, $perPage);

        return $this->successResponse([
            'courses' => CourseResource::collection($courses),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ], 'Courses retrieved successfully.');
    }

    /**
     * Show Course (Owned by Teacher)
     */
    public function show(int $course): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $model = $this->teacherCourseService->findTeacherCourse($teacher->id, $course);

        if (!$model) {
            return $this->errorResponse(ApiErrorCode::NOT_FOUND, 'Course not found.', null, 404);
        }

        return $this->successResponse(new CourseResource($model), 'Course retrieved successfully.');
    }

    /**
     * Store Course (Owned by Teacher)
     *
     * ✅ Security:
     * - program_id MUST belong to the authenticated teacher
     * - owner_teacher_id is forced to teacher->id (no injection)
     * - prices created only for this course (no cross-course writes)
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();
        $data = $request->validated();

        // ✅ do not allow client to set owner_teacher_id
        unset($data['owner_teacher_id']);

        // upload image
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('courses', 'public');
        }

        try {
            $course = $this->teacherCourseService->createCourse($teacher->id, $data);
        } catch (\Throwable $e) {
            // if upload happened and create failed, cleanup uploaded file
            if (!empty($data['image']) && Storage::disk('public')->exists($data['image'])) {
                Storage::disk('public')->delete($data['image']);
            }
            throw $e;
        }

        return $this->successResponse(new CourseResource($course), 'Course created successfully.', 201);
    }

    /**
     * Update Course (Owned by Teacher)
     *
     * ✅ Security:
     * - teacher can update ONLY his courses
     * - teacher cannot change program_id to another teacher program
     * - teacher cannot change owner_teacher_id
     */
    public function update(UpdateCourseRequest $request, int $course): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $model = $this->teacherCourseService->findTeacherCourse($teacher->id, $course);
        if (!$model) {
            return $this->errorResponse(ApiErrorCode::NOT_FOUND, 'Course not found.', null, 404);
        }

        $data = $request->validated();

        // ✅ forbid ownership changes
        unset($data['owner_teacher_id']);

        // remove image
        if (!empty($data['remove_image']) && $model->image) {
            if (Storage::disk('public')->exists($model->image)) {
                Storage::disk('public')->delete($model->image);
            }
            $data['image'] = null;
        }

        // replace image
        if ($request->hasFile('image')) {
            if ($model->image && Storage::disk('public')->exists($model->image)) {
                Storage::disk('public')->delete($model->image);
            }
            $data['image'] = $request->file('image')->store('courses', 'public');
        }

        unset($data['remove_image']);

        try {
            $updated = $this->teacherCourseService->updateCourse($teacher->id, $model->id, $data);
        } catch (\Throwable $e) {
            // if new image uploaded and update failed -> cleanup
            if (!empty($data['image']) && $data['image'] !== $model->image && Storage::disk('public')->exists($data['image'])) {
                Storage::disk('public')->delete($data['image']);
            }
            throw $e;
        }

        return $this->successResponse(new CourseResource($updated), 'Course updated successfully.');
    }

    /**
     * Toggle Active (Owned by Teacher)
     */
    public function toggleActive(int $course): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $model = $this->teacherCourseService->findTeacherCourse($teacher->id, $course);
        if (!$model) {
            return $this->errorResponse(ApiErrorCode::NOT_FOUND, 'Course not found.', null, 404);
        }

        $model = $this->teacherCourseService->toggleActive($model);

        return $this->successResponse(new CourseResource($model), 'Course status updated successfully.');
    }

    /**
     * Get Course Price (Resolved)
     *
     * @queryParam branch_id int Optional branch id. Example: 1
     * @queryParam delivery_type string Optional delivery type. Example: online
     */
    public function price(int $course): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $model = $this->teacherCourseService->findTeacherCourse($teacher->id, $course);
        if (!$model) {
            return $this->errorResponse(ApiErrorCode::NOT_FOUND, 'Course not found.', null, 404);
        }

        $branchId = request()->get('branch_id');
        $deliveryType = request()->get('delivery_type');

        $resolved = $this->coursePriceService->resolvePrice(
            $model->id,
            $branchId !== null ? (int) $branchId : null,
            $deliveryType ? DeliveryType::from($deliveryType) : null
        );

        return $this->successResponse([
            'course_id' => $model->id,
            'price' => $resolved,
        ], 'Course price resolved successfully.');
    }
}
