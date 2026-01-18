<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Services\Student\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student Attendance
 *
 * Student API for attendance reports.
 */
class AttendanceController extends ApiController
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * Attendance Report
     *
     * Get attendance summary and detailed report.
     *
     * @queryParam course_id integer optional Filter by course ID. Example: 1
     * @queryParam from date optional Filter from date (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam to date optional Filter to date (YYYY-MM-DD). Example: 2026-12-31
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Attendance report retrieved successfully.",
     *   "data": {
     *     "summary": {
     *       "total_sessions": 20,
     *       "present": 18,
     *       "absent": 2,
     *       "attendance_rate": 90.0
     *     },
     *     "details": [...]
     *   }
     * }
     */
    public function report(Request $request): JsonResponse
    {
        $student = auth('students')->user();

        $filters = [
            'course_id' => $request->input('course_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $report = $this->attendanceService->getAttendanceReport($student, $filters);

        return $this->successResponse(
            new \App\Http\Resources\V1\Student\AttendanceReportResource($report),
            'Attendance report retrieved successfully.'
        );
    }
}
