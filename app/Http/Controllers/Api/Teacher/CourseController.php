<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Domain\Training\Services\CoursePriceService;
use App\Http\Controllers\ApiController;
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
     * @queryParam program_id int Filter by program. Example: 2
     * @queryParam delivery_type string Filter by delivery type (onsite, online, hybrid). Example: online
     * @queryParam sort string Sort (newest, oldest, name). Example: newest
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $filters = request()->only(['q', 'active', 'program_id', 'delivery_type', 'sort']);
        $perPage = (int) request()->get('per_page', 15);

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
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        return $this->successResponse(new CourseResource($model), 'Course retrieved successfully.');
    }

    /**
     * Store Course (Owned by Teacher)
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();
        $data = $request->validated();

        // upload image
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('courses', 'public');
        }

        $course = $this->teacherCourseService->createCourse($teacher->id, $data);

        return $this->successResponse(new CourseResource($course), 'Course created successfully.', 201);
    }

    /**
     * Update Course (Owned by Teacher)
     */
    public function update(UpdateCourseRequest $request, int $course): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();
        $model = $this->teacherCourseService->findTeacherCourse($teacher->id, $course);

        if (!$model) {
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        $data = $request->validated();

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

        $updated = $this->teacherCourseService->updateCourse($model, $data);

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
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        $model = $this->teacherCourseService->toggleActive($model);

        return $this->successResponse(new CourseResource($model), 'Course status updated successfully.');
    }

    /**
     * Get Course Price (Resolved)
     *
     * Return resolved price for this course based on branch_id and delivery_type.
     *
     * @queryParam branch_id int Optional branch id. Example: 1
     * @queryParam delivery_type string Optional delivery type. Example: online
     */
    public function price(int $course): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $model = $this->teacherCourseService->findTeacherCourse($teacher->id, $course);
        if (!$model) {
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        $branchId = request()->get('branch_id');
        $deliveryType = request()->get('delivery_type');

        $resolved = $this->coursePriceService->resolvePrice(
            $model->id,
            $branchId !== null ? (int) $branchId : null,
            $deliveryType ? \App\Domain\Training\Enums\DeliveryType::from($deliveryType) : null
        );

        return $this->successResponse([
            'course_id' => $model->id,
            'price' => $resolved,
        ], 'Course price resolved successfully.');
    }
}
