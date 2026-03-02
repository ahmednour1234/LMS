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
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->leftJoin('payments', function ($join) {
                $join->on('payments.enrollment_id', '=', 'enrollments.id')
                    ->where('payments.status', 'completed');
            })
            ->select([
                'enrollments.id',
                'enrollments.reference',
                'enrollments.total_amount',
                'students.name as student_name',
                DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(courses.name, '$.en')), JSON_UNQUOTE(JSON_EXTRACT(courses.name, '$.ar')), courses.name) as course_name"),
                DB::raw('COALESCE(SUM(payments.amount), 0) as paid_amount'),
            ])
            ->groupBy('enrollments.id', 'enrollments.reference', 'enrollments.total_amount', 'students.name', 'courses.name');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('enrollments.created_at', [$dateFrom, $dateTo]);
        }

        if ($teacherId) {
            $query->where('courses.owner_teacher_id', $teacherId);
        }

        if ($courseId) {
            $query->where('courses.id', $courseId);
        }

        $enrollments = $query->get();

        $enrollmentsData = $enrollments->map(function ($enrollment) {
            $totalAmount = (float) $enrollment->total_amount;
            $paidAmount = (float) $enrollment->paid_amount;
            $dueAmount = max($totalAmount - $paidAmount, 0);

            // Determine status
            $status = 'pending';
            if ($paidAmount >= $totalAmount) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            }

            return [
                'reference' => $enrollment->reference ?? '',
                'student_name' => $enrollment->student_name ?? '',
                'course_name' => $enrollment->course_name ?? '',
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
            ];
        })->filter(function ($enrollment) {
            // Filter to show only enrollments with due amount > 0
            return $enrollment['due_amount'] > 0;
        })->values()->toArray();

        // Calculate summary - only due amounts
        $totalDue = collect($enrollmentsData)->sum('due_amount');
        $count = count($enrollmentsData);

        $summary = [
            'total_due' => $totalDue,
            'count' => $count,
        ];

        return [
            'summary' => $summary,
            'enrollments' => $enrollmentsData,
        ];
    }
}
