<?php

namespace App\Filament\Teacher\Widgets;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Program;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class TeacherAnalyticsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $teacherId = auth('teacher')->id();

        $programsCount = Program::query()->where('teacher_id', $teacherId)->count();
        $coursesCount = Course::query()->where('owner_teacher_id', $teacherId)->count();

        $enrollments = Enrollment::query()
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->get();

        $enrollmentsCount = $enrollments->count();
        $uniqueStudentsCount = $enrollments->pluck('student_id')->unique()->count();

        $totalPaid = $enrollments->sum(function ($enrollment) {
            return $enrollment->payments()->where('status', 'completed')->sum('amount');
        });

        $totalAmount = $enrollments->sum('total_amount');
        $totalDue = $totalAmount - $totalPaid;

        $courses = Course::query()
            ->where('owner_teacher_id', $teacherId)
            ->withCount(['enrollments'])
            ->get();

        $avgCompletion = $enrollments->avg('progress_percent') ?? 0;
        $avgRating = null;

        if (Schema::hasColumn('courses', 'rating')) {
            $avgRating = $courses->avg('rating') ?? 0;
        }

        $goodCoursesCount = 0;
        $badCoursesCount = 0;

        foreach ($courses as $course) {
            if ($avgRating !== null) {
                $rating = $course->rating ?? 0;
                if ($rating >= 4.0) {
                    $goodCoursesCount++;
                } elseif ($rating <= 2.5) {
                    $badCoursesCount++;
                }
            } else {
                $courseEnrollments = $enrollments->where('course_id', $course->id);
                if ($courseEnrollments->isNotEmpty()) {
                    $completionRate = $courseEnrollments->avg('progress_percent') ?? 0;
                    if ($completionRate >= 70) {
                        $goodCoursesCount++;
                    } elseif ($completionRate < 30) {
                        $badCoursesCount++;
                    }
                }
            }
        }

        $stats = [
            Stat::make(__('dashboard.stats.total_programs') ?? 'Total Programs', $programsCount)
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make(__('dashboard.stats.total_courses') ?? 'Total Courses', $coursesCount)
                ->icon('heroicon-o-book-open')
                ->color('success'),

            Stat::make(__('dashboard.stats.total_enrollments') ?? 'Total Enrollments', $enrollmentsCount)
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make(__('dashboard.stats.unique_students') ?? 'Unique Students', $uniqueStudentsCount)
                ->icon('heroicon-o-user-group')
                ->color('warning'),

            Stat::make(__('dashboard.stats.total_paid') ?? 'Total Paid', number_format($totalPaid, 2) . ' OMR')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('dashboard.stats.total_due') ?? 'Total Due', number_format($totalDue, 2) . ' OMR')
                ->icon('heroicon-o-exclamation-circle')
                ->color($totalDue > 0 ? 'danger' : 'success'),
        ];

        if ($avgRating !== null) {
            $stats[] = Stat::make(__('dashboard.stats.average_rating') ?? 'Average Rating', number_format($avgRating, 1))
                ->icon('heroicon-o-star')
                ->color('warning');
        } else {
            $stats[] = Stat::make(__('dashboard.stats.average_completion') ?? 'Average Completion', number_format($avgCompletion, 1) . '%')
                ->icon('heroicon-o-chart-bar')
                ->color('info');
        }

        $stats[] = Stat::make(__('dashboard.stats.good_courses') ?? 'Good Courses', $goodCoursesCount)
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->description(__('dashboard.stats.bad_courses') ?? 'Bad Courses: ' . $badCoursesCount);

        return $stats;
    }
}
