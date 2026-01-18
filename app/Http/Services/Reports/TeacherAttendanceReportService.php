<?php

namespace App\Http\Services\Reports;

use App\Domain\Training\Models\CourseSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceReportService
{
    public function getReport(
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null,
        int $teacherId,
        ?int $courseId = null
    ): array {
        $query = CourseSession::query()
            ->join('courses', 'course_sessions.course_id', '=', 'courses.id')
            ->leftJoin('course_session_attendances', 'course_sessions.id', '=', 'course_session_attendances.session_id')
            ->leftJoin('enrollments', 'course_session_attendances.enrollment_id', '=', 'enrollments.id')
            ->where('courses.owner_teacher_id', $teacherId)
            ->select([
                'courses.id as course_id',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(courses.name, '$.en')) as course_name"),
                DB::raw('COUNT(DISTINCT course_sessions.id) as sessions_count'),
                DB::raw('COUNT(DISTINCT enrollments.id) as enrollments_count'),
                DB::raw("SUM(CASE WHEN course_session_attendances.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN course_session_attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN course_session_attendances.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw("SUM(CASE WHEN course_session_attendances.status = 'excused' THEN 1 ELSE 0 END) as excused_count"),
            ])
            ->groupBy('courses.id', 'courses.name');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('course_sessions.starts_at', [$dateFrom, $dateTo]);
        }

        if ($courseId) {
            $query->where('courses.id', $courseId);
        }

        $results = $query->get();

        $courses = [];
        $summary = [
            'sessions_count' => 0,
            'enrollments_count' => 0,
            'present_count' => 0,
            'absent_count' => 0,
            'late_count' => 0,
            'excused_count' => 0,
        ];

        foreach ($results as $row) {
            $total = (int) $row->present_count + (int) $row->absent_count + (int) $row->late_count + (int) $row->excused_count;
            $rate = $total > 0 ? ((int) $row->present_count / $total) * 100 : 0;

            $courses[] = [
                'course_id' => $row->course_id,
                'course_name' => $row->course_name,
                'sessions_count' => (int) $row->sessions_count,
                'enrollments_count' => (int) $row->enrollments_count,
                'present_count' => (int) $row->present_count,
                'absent_count' => (int) $row->absent_count,
                'late_count' => (int) $row->late_count,
                'excused_count' => (int) $row->excused_count,
                'attendance_rate' => round($rate, 2),
            ];

            $summary['sessions_count'] += (int) $row->sessions_count;
            $summary['enrollments_count'] += (int) $row->enrollments_count;
            $summary['present_count'] += (int) $row->present_count;
            $summary['absent_count'] += (int) $row->absent_count;
            $summary['late_count'] += (int) $row->late_count;
            $summary['excused_count'] += (int) $row->excused_count;
        }

        $summaryTotal = $summary['present_count'] + $summary['absent_count'] + $summary['late_count'] + $summary['excused_count'];
        $summary['attendance_rate'] = $summaryTotal > 0 ? round(($summary['present_count'] / $summaryTotal) * 100, 2) : 0;

        return [
            'summary' => $summary,
            'courses' => $courses,
        ];
    }
}
