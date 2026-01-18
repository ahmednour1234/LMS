<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Services\CourseService;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Accounting\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student Courses
 *
 * Student API for browsing and viewing courses with enrollment status.
 */
class CourseController extends ApiController
{
    public function __construct(
        private CourseService $courseService
    ) {}

    /**
     * List Courses
     *
     * Get a paginated list of courses with enrollment and payment status.
     *
     * @queryParam q string optional Search courses by title. Example: PHP
     * @queryParam program_id integer optional Filter by program ID. Example: 1
     * @queryParam delivery_type string optional Filter by delivery type: online, onsite, hybrid. Example: online
     * @queryParam branch_id integer optional Filter by branch ID. Example: 1
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     * @queryParam has_price integer optional Only show courses with active prices (1). Example: 1
     * @queryParam sort string optional Sort order: newest, oldest, or title. Default: newest. Example: newest
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Courses retrieved successfully.",
     *   "data": [...],
     *   "meta": {
     *     "pagination": {...}
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $student = auth('students')->user();
        
        $filters = [
            'q' => $request->input('q'),
            'program_id' => $request->input('program_id'),
            'delivery_type' => $request->input('delivery_type'),
            'branch_id' => $request->input('branch_id'),
            'active' => $request->input('active', 1),
            'has_price' => $request->input('has_price'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $courses = $this->courseService->getPaginated($filters, $perPage);

        $courseIds = $courses->pluck('id')->toArray();
        $enrollments = Enrollment::where('student_id', $student->id)
            ->whereIn('course_id', $courseIds)
            ->get()
            ->keyBy('course_id');

        $enrollmentIds = $enrollments->pluck('id')->toArray();
        $payments = Payment::whereIn('enrollment_id', $enrollmentIds)
            ->where('status', 'completed')
            ->selectRaw('enrollment_id, SUM(amount) as total_paid')
            ->groupBy('enrollment_id')
            ->get()
            ->keyBy('enrollment_id');

        $courses->getCollection()->transform(function ($course) use ($enrollments, $payments) {
            $enrollment = $enrollments->get($course->id);
            $paidAmount = $enrollment ? ($payments->get($enrollment->id)->total_paid ?? 0) : 0;
            $dueAmount = $enrollment ? max(0, ($enrollment->total_amount ?? 0) - $paidAmount) : 0;
            $isPaid = $dueAmount <= 0.01;

            $course->is_enrolled = $enrollment !== null;
            $course->enrollment_status = $enrollment?->status->value;
            $course->payment_status = $enrollment ? ($isPaid ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid')) : null;
            $course->paid_amount = $paidAmount;
            $course->due_amount = $dueAmount;

            return $course;
        });

        return $this->paginatedResponse(
            \App\Http\Resources\V1\Student\CourseCardResource::collection($courses),
            'Courses retrieved successfully.'
        );
    }

    /**
     * Show Course
     *
     * Get a single course by ID with enrollment status and payment information.
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "title": {...},
     *     "enrolled_status": "active",
     *     "can_access_content": true,
     *     "remaining_amount": 0
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Course not found.",
     *   "error": {
     *     "code": "NOT_FOUND"
     *   }
     * }
     */
    public function show(int $course): JsonResponse
    {
        $student = auth('students')->user();
        $courseModel = $this->courseService->findByIdWithContent($course);

        if (!$courseModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseModel->id)
            ->first();

        $paidAmount = 0;
        $dueAmount = 0;
        $canAccessContent = false;

        if ($enrollment) {
            $paidAmount = (float) Payment::where('enrollment_id', $enrollment->id)
                ->where('status', 'completed')
                ->sum('amount');
            $dueAmount = max(0, ($enrollment->total_amount ?? 0) - $paidAmount);
            $canAccessContent = $dueAmount <= 0.01 || ($courseModel->allow_unpaid_access ?? false);
        }

        $courseModel->setAttribute('enrolled_status', $enrollment ? $enrollment->status->value : 'not_enrolled');
        $courseModel->setAttribute('can_access_content', $canAccessContent);
        $courseModel->setAttribute('remaining_amount', $dueAmount);

        return $this->successResponse(
            new \App\Http\Resources\V1\Student\CourseShowResource($courseModel),
            'Course retrieved successfully.'
        );
    }
}
