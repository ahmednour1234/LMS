<?php

namespace App\Http\Services;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSessionAttendance;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeacherReportsService
{
    private function teacherCoursesQuery(int $teacherId): Builder
    {
        // owner_teacher_id موجود عندك في CourseController security
        return Course::query()->where('owner_teacher_id', $teacherId);
    }

    private function assertTeacherOwnsCourse(int $teacherId, ?int $courseId): void
    {
        if (!$courseId) return;

        $ok = $this->teacherCoursesQuery($teacherId)->where('id', $courseId)->exists();
        if (!$ok) {
            throw new \DomainException('COURSE_NOT_OWNED');
        }
    }

    private function parseDate(?string $v): ?Carbon
    {
        return $v ? Carbon::parse($v)->startOfDay() : null;
    }

    private function parseDateTo(?string $v): ?Carbon
    {
        return $v ? Carbon::parse($v)->endOfDay() : null;
    }

    private function groupKey(string $groupBy, string $column = 'created_at'): string
    {
        // MySQL compatible grouping keys
        return match ($groupBy) {
            'week' => "DATE_FORMAT($column, '%x-%v')",
            'month' => "DATE_FORMAT($column, '%Y-%m')",
            default => "DATE_FORMAT($column, '%Y-%m-%d')",
        };
    }

    /**
     * Revenue Report (like Filament page)
     */
    public function revenueReport(int $teacherId, array $filters): array
    {
        $courseId = $filters['course_id'] ?? null;
        $this->assertTeacherOwnsCourse($teacherId, $courseId);

        $dateFrom = $this->parseDate($filters['date_from'] ?? null);
        $dateTo   = $this->parseDateTo($filters['date_to'] ?? null);
        $paymentStatus = $filters['payment_status'] ?? null;

        // Base enrollments query scoped to teacher courses
        $enrollments = Enrollment::query()
            ->select([
                'enrollments.id',
                'enrollments.course_id',
                'enrollments.total_amount',
                'enrollments.created_at',
            ])
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId);

        if ($courseId) $enrollments->where('enrollments.course_id', $courseId);
        if ($dateFrom) $enrollments->where('enrollments.created_at', '>=', $dateFrom);
        if ($dateTo)   $enrollments->where('enrollments.created_at', '<=', $dateTo);

        // Summaries per course
        $courseRows = (clone $enrollments)
            ->select([
                'enrollments.course_id',
                DB::raw('COUNT(enrollments.id) as enrollments_count'),
                DB::raw('COALESCE(SUM(enrollments.total_amount),0) as total_amount'),
            ])
            ->groupBy('enrollments.course_id')
            ->get()
            ->keyBy('course_id');

        // Payments scoped by enrollments + teacher courses
        $payments = Payment::query()
            ->join('enrollments', 'enrollments.id', '=', 'payments.enrollment_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId);

        if ($courseId) $payments->where('enrollments.course_id', $courseId);
        if ($dateFrom) $payments->where('payments.created_at', '>=', $dateFrom);
        if ($dateTo)   $payments->where('payments.created_at', '<=', $dateTo);
        if ($paymentStatus) $payments->where('payments.status', $paymentStatus);

        $paidPerCourse = (clone $payments)
            ->select([
                'enrollments.course_id',
                DB::raw("COALESCE(SUM(CASE WHEN payments.status = 'completed' THEN payments.amount ELSE 0 END),0) as total_paid"),
            ])
            ->groupBy('enrollments.course_id')
            ->get()
            ->keyBy('course_id');

        $courses = $this->teacherCoursesQuery($teacherId)
            ->when($courseId, fn($q) => $q->where('id', $courseId))
            ->get()
            ->map(function ($course) use ($courseRows, $paidPerCourse) {
                $name = is_array($course->name) ? ($course->name['en'] ?? $course->name['ar'] ?? '') : $course->name;

                $total = (float) ($courseRows[$course->id]->total_amount ?? 0);
                $paid  = (float) ($paidPerCourse[$course->id]->total_paid ?? 0);

                return [
                    'course_id' => $course->id,
                    'course_name' => $name,
                    'enrollments_count' => (int) ($courseRows[$course->id]->enrollments_count ?? 0),
                    'total_amount' => $total,
                    'total_paid' => $paid,
                    'outstanding' => max($total - $paid, 0),
                ];
            })
            ->values();

        $summary = [
            'total_enrollments' => (int) $courses->sum('enrollments_count'),
            'total_amount' => (float) $courses->sum('total_amount'),
            'total_paid' => (float) $courses->sum('total_paid'),
            'outstanding' => (float) $courses->sum('outstanding'),
        ];

        return [
            'summary' => $summary,
            'courses' => $courses,
            'filters' => [
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'course_id' => $courseId,
                'payment_status' => $paymentStatus,
            ],
        ];
    }

    /**
     * Stats: Cards + Charts-ready series
     */
    public function stats(int $teacherId, array $filters): array
    {
        $courseId = $filters['course_id'] ?? null;
        $this->assertTeacherOwnsCourse($teacherId, $courseId);

        $dateFrom = $this->parseDate($filters['date_from'] ?? null);
        $dateTo   = $this->parseDateTo($filters['date_to'] ?? null);
        $groupBy = $filters['group_by'] ?? 'day';

        // ENROLLMENTS base
        $enr = Enrollment::query()
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId);

        if ($courseId) $enr->where('enrollments.course_id', $courseId);
        if ($dateFrom) $enr->where('enrollments.created_at', '>=', $dateFrom);
        if ($dateTo)   $enr->where('enrollments.created_at', '<=', $dateTo);

        $enrollmentsTotal = (clone $enr)->count();
        $revenueTotal = (float) (clone $enr)->sum('enrollments.total_amount');

        // PAYMENTS base
        $pay = Payment::query()
            ->join('enrollments', 'enrollments.id', '=', 'payments.enrollment_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId);

        if ($courseId) $pay->where('enrollments.course_id', $courseId);
        if ($dateFrom) $pay->where('payments.created_at', '>=', $dateFrom);
        if ($dateTo)   $pay->where('payments.created_at', '<=', $dateTo);

        $paymentsTotal = (clone $pay)->count();
        $paymentsCompleted = (clone $pay)->where('payments.status', 'completed')->count();
        $paymentsPending   = (clone $pay)->where('payments.status', 'pending')->count();
        $paymentsFailed    = (clone $pay)->where('payments.status', 'failed')->count();

        $revenuePaid = (float) (clone $pay)->where('payments.status', 'completed')->sum('payments.amount');
        $outstanding = max($revenueTotal - $revenuePaid, 0);

        // Charts series: enrollments per period + paid per period
        $keyEnr = $this->groupKey($groupBy, 'enrollments.created_at');
        $enrollmentsSeries = (clone $enr)
            ->select([
                DB::raw("$keyEnr as period"),
                DB::raw('COUNT(enrollments.id) as value'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn($r) => ['x' => $r->period, 'y' => (int) $r->value])
            ->values();

        $keyPay = $this->groupKey($groupBy, 'payments.created_at');
        $paidSeries = (clone $pay)
            ->select([
                DB::raw("$keyPay as period"),
                DB::raw("COALESCE(SUM(CASE WHEN payments.status='completed' THEN payments.amount ELSE 0 END),0) as value"),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn($r) => ['x' => $r->period, 'y' => (float) $r->value])
            ->values();

        $cards = [
            ['key' => 'enrollments_total', 'label' => __('reports.stats.enrollments_total'), 'value' => $enrollmentsTotal],
            ['key' => 'payments_total', 'label' => __('reports.stats.payments_total'), 'value' => $paymentsTotal],
            ['key' => 'payments_completed', 'label' => __('reports.stats.payments_completed'), 'value' => $paymentsCompleted],
            ['key' => 'payments_pending', 'label' => __('reports.stats.payments_pending'), 'value' => $paymentsPending],
            ['key' => 'payments_failed', 'label' => __('reports.stats.payments_failed'), 'value' => $paymentsFailed],
            ['key' => 'revenue_total', 'label' => __('reports.stats.revenue_total'), 'value' => $revenueTotal],
            ['key' => 'revenue_paid', 'label' => __('reports.stats.revenue_paid'), 'value' => $revenuePaid],
            ['key' => 'revenue_outstanding', 'label' => __('reports.stats.revenue_outstanding'), 'value' => $outstanding],
        ];

        return [
            'cards' => $cards,
            'charts' => [
                [
                    'key' => 'enrollments_series',
                    'label' => __('reports.stats.enrollments_total'),
                    'type' => 'line',
                    'series' => $enrollmentsSeries,
                ],
                [
                    'key' => 'paid_series',
                    'label' => __('reports.stats.revenue_paid'),
                    'type' => 'bar',
                    'series' => $paidSeries,
                ],
            ],
        ];
    }

    /**
     * Attendance Summary (cards + charts)
     */
    public function attendanceSummary(int $teacherId, array $filters): array
    {
        $courseId = $filters['course_id'] ?? null;
        $sessionId = $filters['session_id'] ?? null;
        $status = $filters['status'] ?? null;

        $this->assertTeacherOwnsCourse($teacherId, $courseId);

        $dateFrom = $this->parseDate($filters['date_from'] ?? null);
        $dateTo   = $this->parseDateTo($filters['date_to'] ?? null);
        $groupBy  = $filters['group_by'] ?? 'day';

        // Attendance scoped: attendance -> enrollment -> course -> teacher
        $att = CourseSessionAttendance::query()
            ->join('enrollments', 'enrollments.id', '=', 'course_session_attendances.enrollment_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId);

        if ($courseId) $att->where('enrollments.course_id', $courseId);
        if ($sessionId) $att->where('course_session_attendances.session_id', $sessionId);
        if ($status) $att->where('course_session_attendances.status', $status);
        if ($dateFrom) $att->where('course_session_attendances.created_at', '>=', $dateFrom);
        if ($dateTo)   $att->where('course_session_attendances.created_at', '<=', $dateTo);

        $total = (clone $att)->count();
        $present = (clone $att)->where('course_session_attendances.status', 'present')->count();
        $absent  = (clone $att)->where('course_session_attendances.status', 'absent')->count();

        $key = $this->groupKey($groupBy, 'course_session_attendances.created_at');

        $series = (clone $att)
            ->select([
                DB::raw("$key as period"),
                DB::raw("SUM(CASE WHEN course_session_attendances.status='present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN course_session_attendances.status='absent' THEN 1 ELSE 0 END) as absent"),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn($r) => [
                'x' => $r->period,
                'present' => (int) $r->present,
                'absent' => (int) $r->absent,
            ])
            ->values();

        return [
            'cards' => [
                ['key' => 'attendance_total', 'label' => __('reports.stats.attendance_total'), 'value' => $total],
                ['key' => 'attendance_present', 'label' => __('reports.stats.attendance_present'), 'value' => $present],
                ['key' => 'attendance_absent', 'label' => __('reports.stats.attendance_absent'), 'value' => $absent],
            ],
            'charts' => [
                [
                    'key' => 'attendance_series',
                    'label' => __('reports.stats.attendance_total'),
                    'type' => 'multi_bar',
                    'series' => $series,
                ],
            ],
        ];
    }

    /**
     * Students report (paginated list) scoped to teacher
     */
    public function studentsReport(int $teacherId, array $filters): array
    {
        $courseId = $filters['course_id'] ?? null;
        $this->assertTeacherOwnsCourse($teacherId, $courseId);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $dateFrom = $this->parseDate($filters['date_from'] ?? null);
        $dateTo   = $this->parseDateTo($filters['date_to'] ?? null);

        $q = Enrollment::query()
            ->with(['student', 'course'])
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId);

        if ($courseId) $q->where('enrollments.course_id', $courseId);
        if (!empty($filters['status'])) $q->where('enrollments.status', $filters['status']);
        if ($dateFrom) $q->where('enrollments.created_at', '>=', $dateFrom);
        if ($dateTo)   $q->where('enrollments.created_at', '<=', $dateTo);

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $q->whereHas('student', function ($qq) use ($term) {
                $qq->where('name', 'like', "%$term%")
                   ->orWhere('email', 'like', "%$term%")
                   ->orWhere('phone', 'like', "%$term%");
            });
        }

        // payment_status filter via payments relation
        if (!empty($filters['payment_status'])) {
            $ps = $filters['payment_status'];
            $q->whereHas('payments', fn($pp) => $pp->where('status', $ps));
        }

        $paginated = $q->select('enrollments.*')->orderByDesc('enrollments.id')->paginate($perPage);

        // Map minimal payload (charts UI friendly)
        $items = $paginated->getCollection()->map(function ($enr) {
            $courseName = is_array($enr->course?->name) ? ($enr->course->name['en'] ?? $enr->course->name['ar'] ?? '') : ($enr->course?->name ?? '');

            return [
                'enrollment_id' => $enr->id,
                'reference' => $enr->reference,
                'course' => ['id' => $enr->course_id, 'name' => $courseName],
                'student' => [
                    'id' => $enr->student_id,
                    'name' => $enr->student?->name,
                    'email' => $enr->student?->email,
                    'phone' => $enr->student?->phone,
                ],
                'status' => (string) $enr->status,
                'total_amount' => (float) $enr->total_amount,
                'enrolled_at' => optional($enr->enrolled_at)->toISOString(),
            ];
        });

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ];
    }

    /**
     * Single student details + attendance + payments within teacher scope
     */
    public function studentDetails(int $teacherId, int $studentId): ?array
    {
        // Find enrollments for this student under teacher courses
        $enrollments = Enrollment::query()
            ->with(['course', 'payments'])
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId)
            ->where('enrollments.student_id', $studentId)
            ->select('enrollments.*')
            ->get();

        if ($enrollments->isEmpty()) return null;

        $courses = $enrollments->map(function ($enr) {
            $courseName = is_array($enr->course?->name) ? ($enr->course->name['en'] ?? $enr->course->name['ar'] ?? '') : ($enr->course?->name ?? '');
            $paid = (float) $enr->payments->where('status', 'completed')->sum('amount');

            return [
                'course_id' => $enr->course_id,
                'course_name' => $courseName,
                'enrollment_id' => $enr->id,
                'reference' => $enr->reference,
                'status' => (string) $enr->status,
                'total_amount' => (float) $enr->total_amount,
                'paid' => $paid,
                'outstanding' => max((float)$enr->total_amount - $paid, 0),
            ];
        })->values();

        // Attendance totals for this student under teacher
        $attendance = CourseSessionAttendance::query()
            ->join('enrollments', 'enrollments.id', '=', 'course_session_attendances.enrollment_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.owner_teacher_id', $teacherId)
            ->where('enrollments.student_id', $studentId);

        $attTotal = (clone $attendance)->count();
        $attPresent = (clone $attendance)->where('course_session_attendances.status', 'present')->count();
        $attAbsent  = (clone $attendance)->where('course_session_attendances.status', 'absent')->count();

        return [
            'student_id' => $studentId,
            'courses' => $courses,
            'attendance' => [
                'total' => $attTotal,
                'present' => $attPresent,
                'absent' => $attAbsent,
            ],
        ];
    }
}
