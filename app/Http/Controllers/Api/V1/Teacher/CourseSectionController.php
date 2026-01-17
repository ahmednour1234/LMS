<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSection;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreCourseSectionRequest;
use App\Http\Requests\Teacher\UpdateCourseSectionRequest;
use App\Http\Resources\Api\V1\Public\CourseSectionResource;
use App\Http\Services\CourseSectionService;
use App\Http\Services\TeacherOwnershipGuardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher - Course Sections
 */
class CourseSectionController extends ApiController
{
    public function __construct(
        protected CourseSectionService $service,
        protected TeacherOwnershipGuardService $guard
    ) {}

    /**
     * List sections
     * @queryParam course_id int Filter. Example: 10
     * @queryParam active boolean Example: 1
     * @queryParam q string Search in title. Example: section
     * @queryParam sort string order|newest|oldest Example: order
     * @queryParam per_page int Example: 15
     */
    public function index(): JsonResponse
    {
        $teacherId = Auth::guard('teacher-api')->id();

        $filters = request()->only(['course_id','active','q','sort']);
        $perPage = (int) request('per_page', 15);

        // enforce ownership if course_id provided
        if (!empty($filters['course_id'])) {
            $course = Course::findOrFail((int) $filters['course_id']);
            $this->guard->assertCourseOwner($teacherId, $course);
        }

        $data = $this->service->paginate($filters, $perPage);

        return $this->successResponse(
            CourseSectionResource::collection($data)->response()->getData(true),
            'OK'
        );
    }

    /**
     * Store section
     */
    public function store(StoreCourseSectionRequest $request): JsonResponse
    {
        $teacherId = Auth::guard('teacher-api')->id();

        $data = $request->validated();
        $course = Course::findOrFail((int) $data['course_id']);
        $this->guard->assertCourseOwner($teacherId, $course);

        $section = $this->service->create($data);

        return $this->successResponse(new CourseSectionResource($section), 'Created', 201);
    }

    /**
     * Show section
     */
    public function show(CourseSection $section): JsonResponse
    {
        $teacherId = Auth::guard('teacher-api')->id();
        $this->guard->assertSectionOwner($teacherId, $section);

        return $this->successResponse(new CourseSectionResource($section), 'OK');
    }

    /**
     * Update section
     */
    public function update(UpdateCourseSectionRequest $request, CourseSection $section): JsonResponse
    {
        $teacherId = Auth::guard('teacher-api')->id();
        $this->guard->assertSectionOwner($teacherId, $section);

        $updated = $this->service->update($section, $request->validated());

        return $this->successResponse(new CourseSectionResource($updated), 'Updated');
    }

    /**
     * Toggle active
     * @bodyParam is_active boolean optional Example: 1
     */
    public function toggleActive(CourseSection $section): JsonResponse
    {
        $teacherId = Auth::guard('teacher-api')->id();
        $this->guard->assertSectionOwner($teacherId, $section);

        $active = request()->has('is_active') ? (bool) request('is_active') : null;
        $updated = $this->service->toggleActive($section, $active);

        return $this->successResponse(new CourseSectionResource($updated), 'OK');
    }
}
