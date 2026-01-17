<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\TeacherAttendanceReportRequest;
use App\Http\Requests\Teacher\TeacherRevenueReportRequest;
use App\Http\Requests\Teacher\TeacherStudentsReportRequest;
use App\Http\Resources\Api\V1\Public\TeacherStatsResource;
use App\Http\Services\TeacherReportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher Reports
 *
 * Dashboard reports & analytics for the authenticated teacher.
 * Security: returns ONLY data related to teacher-owned courses.
 */
class TeacherReportController extends ApiController
{
    public function __construct(
        protected TeacherReportsService $service
    ) {}

    /**
     * Revenue Report
     *
     * @queryParam date_from date Optional. Example: 2026-01-01
     * @queryParam date_to date Optional. Example: 2026-01-31
     * @queryParam course_id int Optional (must belong to teacher). Example: 10
     * @queryParam payment_status string Optional. Example: completed
     * @queryParam group_by string Optional (day|week|month). Example: day
     */
    public function revenue(TeacherRevenueReportRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();
        $data = $this->service->revenueReport($teacher->id, $request->validated());

        return $this->successResponse($data, __('Reports retrieved successfully.'));
    }

    /**
     * Stats (Cards + Charts)
     *
     * @queryParam date_from date Optional. Example: 2026-01-01
     * @queryParam date_to date Optional. Example: 2026-01-31
     * @queryParam course_id int Optional (must belong to teacher). Example: 10
     * @queryParam group_by string Optional (day|week|month). Example: week
     */
    public function stats(TeacherRevenueReportRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();
        $payload = $this->service->stats($teacher->id, $request->validated());

        return $this->successResponse(new TeacherStatsResource($payload), __('Reports retrieved successfully.'));
    }

    /**
     * Attendance Summary (Cards + Charts)
     *
     * @queryParam date_from date Optional. Example: 2026-01-01
     * @queryParam date_to date Optional. Example: 2026-01-31
     * @queryParam course_id int Optional (must belong to teacher). Example: 10
     * @queryParam session_id int Optional (must belong to teacher course). Example: 55
     * @queryParam status string Optional. Example: present
     * @queryParam group_by string Optional (day|week|month). Example: day
     */
    public function attendanceSummary(TeacherAttendanceReportRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();
        $data = $this->service->attendanceSummary($teacher->id, $request->validated());

        return $this->successResponse($data, __('Reports retrieved successfully.'));
    }

    /**
     * Students Report (Paginated)
     *
     * @queryParam q string Search by student name/email/phone. Example: ahmed
     * @queryParam course_id int Optional (must belong to teacher). Example: 10
     * @queryParam status string Optional enrollment status. Example: active
     * @queryParam payment_status string Optional payment status. Example: completed
     * @queryParam date_from date Optional. Example: 2026-01-01
     * @queryParam date_to date Optional. Example: 2026-01-31
     * @queryParam per_page int Optional. Example: 15
     */
    public function studentsReport(TeacherStudentsReportRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();
        $result = $this->service->studentsReport($teacher->id, $request->validated());

        return $this->successResponse($result, __('Reports retrieved successfully.'));
    }

    /**
     * Single Student Details (under teacher scope)
     */
    public function studentDetails(int $student): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();
        $result = $this->service->studentDetails($teacher->id, $student);

        if (!$result) {
            return $this->errorResponse(\App\Http\Enums\ApiErrorCode::NOT_FOUND, 'Student not found.', null, 404);
        }

        return $this->successResponse($result, __('Reports retrieved successfully.'));
    }
}
