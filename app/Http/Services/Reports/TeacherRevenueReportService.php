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
            ->leftJoin('payments', function ($join) {
                $join->on('payments.enrollment_id', '=', 'enrollments.id')
                    ->where('payments.status', 'completed');
            })
            ->select([
                'teachers.id as teacher_id',
                'teachers.name as teacher_name',
                'courses.id as course_id',
                DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(courses.name, '$.en')), JSON_UNQUOTE(JSON_EXTRACT(courses.name, '$.ar')), courses.name) as course_name"),
                DB::raw('COUNT(DISTINCT enrollments.id) as enrollments_count'),
                DB::raw('SUM(enrollments.total_amount) as total_amount'),
                DB::raw('COALESCE(SUM(payments.amount), 0) as total_paid'),
            ])
            ->groupBy('teachers.id', 'teachers.name', 'courses.id', 'courses.name');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('enrollments.created_at', [$dateFrom, $dateTo]);
        }

        if ($teacherId) {
            $query->where('courses.owner_teacher_id', $teacherId);
        }

        if ($courseId) {
            $query->where('courses.id', $courseId);
        }

        if ($paymentStatus) {
            if ($paymentStatus === 'paid') {
                $query->havingRaw('COALESCE(SUM(payments.amount), 0) >= SUM(enrollments.total_amount)');
            } elseif ($paymentStatus === 'partial') {
                $query->havingRaw('COALESCE(SUM(payments.amount), 0) > 0 AND COALESCE(SUM(payments.amount), 0) < SUM(enrollments.total_amount)');
            } elseif ($paymentStatus === 'pending') {
                $query->havingRaw('COALESCE(SUM(payments.amount), 0) = 0');
            }
        }

        $results = $query->get();

        $teachersMap = [];
        foreach ($results as $row) {
            $teacherId = $row->teacher_id;
            $courseId = $row->course_id;

            if (!isset($teachersMap[$teacherId])) {
                $teachersMap[$teacherId] = [
                    'teacher_id' => $teacherId,
                    'teacher_name' => $row->teacher_name,
                    'courses' => [],
                    'total_amount' => 0,
                    'total_paid' => 0,
                    'enrollments_count' => 0,
                ];
            }

            $totalAmount = (float) $row->total_amount;
            $totalPaid = (float) $row->total_paid;
            $outstanding = $totalAmount - $totalPaid;

            $teachersMap[$teacherId]['courses'][] = [
                'course_id' => $courseId,
                'course_name' => $row->course_name,
                'enrollments_count' => (int) $row->enrollments_count,
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'outstanding' => $outstanding,
            ];

            $teachersMap[$teacherId]['total_amount'] += $totalAmount;
            $teachersMap[$teacherId]['total_paid'] += $totalPaid;
            $teachersMap[$teacherId]['enrollments_count'] += (int) $row->enrollments_count;
        }

        $teachers = array_values(array_map(function ($teacher) {
            $teacher['outstanding'] = $teacher['total_amount'] - $teacher['total_paid'];
            $teacher['courses_count'] = count($teacher['courses']);
            return $teacher;
        }, $teachersMap));

        $summary = [
            'total_revenue' => collect($teachers)->sum('total_amount'),
            'total_paid' => collect($teachers)->sum('total_paid'),
            'total_outstanding' => collect($teachers)->sum('outstanding'),
        ];

        return [
            'summary' => $summary,
            'teachers' => $teachers,
        ];
    }
}
