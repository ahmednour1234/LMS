<?php

namespace App\Http\Services;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\CourseSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function markAttendance(int $sessionId, array $attendanceData): void
    {
        $session = CourseSession::findOrFail($sessionId);
        $markedBy = Auth::id();
        $markedAt = now();

        $records = [];
        foreach ($attendanceData as $enrollmentId => $status) {
            $records[] = [
                'session_id' => $sessionId,
                'enrollment_id' => $enrollmentId,
                'status' => $status,
                'method' => 'manual',
                'marked_by' => $markedBy,
                'marked_at' => $markedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($records)) {
            DB::table('course_session_attendances')->upsert(
                $records,
                ['session_id', 'enrollment_id'],
                ['status', 'method', 'marked_by', 'marked_at', 'updated_at']
            );
        }
    }

    public function markAllPresent(int $sessionId, int $courseId): void
    {
        $enrollments = Enrollment::where('course_id', $courseId)
            ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
            ->pluck('id');

        $attendanceData = $enrollments->mapWithKeys(function ($id) {
            return [$id => 'present'];
        })->toArray();

        $this->markAttendance($sessionId, $attendanceData);
    }

    public function markAllAbsent(int $sessionId, int $courseId): void
    {
        $enrollments = Enrollment::where('course_id', $courseId)
            ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
            ->pluck('id');

        $attendanceData = $enrollments->mapWithKeys(function ($id) {
            return [$id => 'absent'];
        })->toArray();

        $this->markAttendance($sessionId, $attendanceData);
    }

    public function getAttendanceForSession(int $sessionId): Collection
    {
        return \App\Domain\Training\Models\CourseSessionAttendance::where('session_id', $sessionId)
            ->with(['enrollment.student', 'markedBy'])
            ->get();
    }
}
