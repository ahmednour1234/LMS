<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Domain\Training\Models\CourseSession;
use App\Domain\Enrollment\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student Sessions
 *
 * Student API for viewing course sessions and attendance.
 */
class SessionController extends ApiController
{
    /**
     * List Sessions
     *
     * Get all sessions for a course with attendance status.
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Sessions retrieved successfully.",
     *   "data": [...]
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "You are not enrolled in this course.",
     *   "error": {
     *     "code": "FORBIDDEN"
     *   }
     * }
     */
    public function index(Request $request, int $course): JsonResponse
    {
        $student = auth('students')->user();
        $enrollment = $request->attributes->get('enrollment');

        $sessions = CourseSession::where('course_id', $course)
            ->with(['lesson', 'teacher'])
            ->orderBy('starts_at', 'asc')
            ->get();

        $sessionIds = $sessions->pluck('id')->toArray();
        $attendances = \App\Domain\Training\Models\CourseSessionAttendance::where('enrollment_id', $enrollment->id)
            ->whereIn('session_id', $sessionIds)
            ->get()
            ->keyBy('session_id');

        $sessions->transform(function ($session) use ($attendances) {
            $attendance = $attendances->get($session->id);
            $session->setAttribute('attendance_status', $attendance?->status->value ?? 'absent');
            $session->setAttribute('attendance_marked_at', $attendance?->marked_at?->toISOString());
            return $session;
        });

        return $this->successResponse(
            \App\Http\Resources\V1\Student\SessionResource::collection($sessions),
            'Sessions retrieved successfully.'
        );
    }

    /**
     * Check In to Session
     *
     * Student self check-in for a session.
     *
     * @urlParam session integer required The ID of the session. Example: 1
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Check-in successful.",
     *   "data": {
     *     "id": 1,
     *     "status": "present",
     *     "marked_at": "2026-01-18T15:00:00Z"
     *   }
     * }
     */
    public function checkIn(int $session): JsonResponse
    {
        $student = auth('students')->user();
        $attendanceService = new \App\Services\Student\AttendanceService();

        $attendance = $attendanceService->checkIn($student, $session);

        return $this->createdResponse(
            new \App\Http\Resources\V1\Student\AttendanceResource($attendance),
            'Check-in successful.'
        );
    }
}
