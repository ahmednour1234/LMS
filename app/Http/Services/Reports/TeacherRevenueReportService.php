<?php

namespace App\Http\Services\Reports;

use App\Domain\Enrollment\Models\Enrollment;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherRevenueReportService
{
    public function getReport(
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null,
        ?int $teacherId = null,
        ?int $courseId = null,
        ?string $paymentStatus = null
    ): array {
        $query = Enrollment::query()
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->join('teachers', 'courses.owner_teacher_id', '=', 'teachers.id')
            ->leftJoin('payments', function ($join) use ($dateFrom, $dateTo, $paymentStatus) {
                $join->on('payments.enrollment_id', '=', 'enrollments.id');
                if ($dateFrom && $dateTo) {
                    $join->whereBetween('payments.created_at', [$dateFrom, $dateTo]);
                }
                if ($paymentStatus) {
                    $join->where('payments.status', $paymentStatus);
                }
            })
            ->select([
                'teachers.id as teacher_id',
                'teachers.name as teacher_name',
                'courses.id as course_id',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(courses.name, '$.en')) as course_name"),
                DB::raw('COUNT(DISTINCT enrollments.id) as enrollments_count'),
                DB::raw('COALESCE(SUM(enrollments.total_amount), 0) as total_amount'),
                DB::raw('COALESCE(SUM(payments.amount), 0) as total_paid'),
            ])
            ->groupBy('teachers.id', 'teachers.name', 'courses.id', 'courses.name');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('enrollments.created_at', [$dateFrom, $dateTo]);
        }

        if ($teacherId) {
            $query->where('teachers.id', $teacherId);
        }

        if ($courseId) {
            $query->where('courses.id', $courseId);
        }

        $results = $query->get();

        $grouped = $results->groupBy('teacher_id');
        $teachers = [];
        $summary = ['total_revenue' => 0, 'total_paid' => 0, 'total_outstanding' => 0];

        foreach ($grouped as $teacherId => $courses) {
            $teacherData = $courses->first();
            $teacherTotalAmount = $courses->sum('total_amount');
            $teacherTotalPaid = $courses->sum('total_paid');
            $teacherOutstanding = $teacherTotalAmount - $teacherTotalPaid;

            $courseList = $courses->map(function ($row) {
                return [
                    'course_id' => $row->course_id,
                    'course_name' => $row->course_name,
                    'enrollments_count' => (int) $row->enrollments_count,
                    'total_amount' => (float) $row->total_amount,
                    'total_paid' => (float) $row->total_paid,
                    'outstanding' => (float) $row->total_amount - (float) $row->total_paid,
                ];
            })->values()->toArray();

            $teachers[] = [
                'teacher_id' => (int) $teacherId,
                'teacher_name' => $teacherData->teacher_name,
                'courses_count' => count($courseList),
                'enrollments_count' => (int) $courses->sum('enrollments_count'),
                'total_amount' => $teacherTotalAmount,
                'total_paid' => $teacherTotalPaid,
                'outstanding' => $teacherOutstanding,
                'courses' => $courseList,
            ];

            $summary['total_revenue'] += $teacherTotalAmount;
            $summary['total_paid'] += $teacherTotalPaid;
        }

        $summary['total_outstanding'] = $summary['total_revenue'] - $summary['total_paid'];

        return [
            'summary' => $summary,
            'teachers' => $teachers,
        ];
    }
}
