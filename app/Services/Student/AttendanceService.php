<?php

namespace App\Services\Student;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\CourseSession;
use App\Domain\Training\Models\CourseSessionAttendance;
use App\Domain\Training\Enums\AttendanceStatus;
use App\Domain\Training\Enums\AttendanceMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function checkIn(Student $student, int $sessionId): CourseSessionAttendance
    {
        $session = CourseSession::findOrFail($sessionId);

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $session->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->firstOrFail();

        $existing = CourseSessionAttendance::where('session_id', $sessionId)
            ->where('enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return CourseSessionAttendance::create([
            'session_id' => $sessionId,
            'enrollment_id' => $enrollment->id,
            'status' => AttendanceStatus::PRESENT,
            'method' => AttendanceMethod::STUDENT_CHECKIN,
            'marked_at' => now(),
        ]);
    }

    public function getAttendanceReport(
        Student $student,
        array $filters = []
    ): array {
        $query = Enrollment::where('student_id', $student->id)
            ->with(['attendances.session']);

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['from'])) {
            $query->whereHas('attendances.session', function ($q) use ($filters) {
                $q->whereDate('starts_at', '>=', $filters['from']);
            });
        }

        if (isset($filters['to'])) {
            $query->whereHas('attendances.session', function ($q) use ($filters) {
                $q->whereDate('starts_at', '<=', $filters['to']);
            });
        }

        $enrollments = $query->get();

        $summary = [
            'total_sessions' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'attendance_rate' => 0,
        ];

        $details = [];

        foreach ($enrollments as $enrollment) {
            $courseSessions = CourseSession::where('course_id', $enrollment->course_id)
                ->when(isset($filters['from']), function ($q) use ($filters) {
                    $q->whereDate('starts_at', '>=', $filters['from']);
                })
                ->when(isset($filters['to']), function ($q) use ($filters) {
                    $q->whereDate('starts_at', '<=', $filters['to']);
                })
                ->get();

            foreach ($courseSessions as $session) {
                $attendance = CourseSessionAttendance::where('session_id', $session->id)
                    ->where('enrollment_id', $enrollment->id)
                    ->first();

                $summary['total_sessions']++;

                if ($attendance) {
                    $status = $attendance->status->value ?? 'absent';
                    if ($status === 'present') {
                        $summary['present']++;
                    } elseif ($status === 'late') {
                        $summary['late']++;
                        $summary['present']++;
                    } else {
                        $summary['absent']++;
                    }
                } else {
                    $summary['absent']++;
                }

                $details[] = [
                    'session_id' => $session->id,
                    'course_id' => $session->course_id,
                    'session_title' => $session->title,
                    'starts_at' => $session->starts_at?->toISOString(),
                    'ends_at' => $session->ends_at?->toISOString(),
                    'attendance_status' => $attendance?->status->value ?? 'absent',
                    'marked_at' => $attendance?->marked_at?->toISOString(),
                ];
            }
        }

        if ($summary['total_sessions'] > 0) {
            $summary['attendance_rate'] = ($summary['present'] / $summary['total_sessions']) * 100;
        }

        return [
            'summary' => $summary,
            'details' => $details,
        ];
    }
}
