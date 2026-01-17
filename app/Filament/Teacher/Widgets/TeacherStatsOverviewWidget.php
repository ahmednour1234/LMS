<?php

namespace App\Filament\Teacher\Widgets;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\CourseSession;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Program;
use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class TeacherStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $teacherId = auth('teacher')->id();

        return [
            Stat::make(__('dashboard.stats.total_programs') ?? 'Total Programs', Program::query()->where('teacher_id', $teacherId)->count())
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),
            Stat::make(__('dashboard.stats.total_courses') ?? 'Total Courses', Course::query()->where('owner_teacher_id', $teacherId)->count())
                ->icon('heroicon-o-book-open')
                ->color('success'),
            Stat::make(__('dashboard.stats.total_sections') ?? 'Total Sections', CourseSection::query()->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))->count())
                ->icon('heroicon-o-rectangle-stack')
                ->color('info'),
            Stat::make(__('dashboard.stats.total_sessions') ?? 'Total Sessions', CourseSession::query()->where('teacher_id', $teacherId)->count())
                ->icon('heroicon-o-calendar-days')
                ->color('warning'),
            Stat::make(__('dashboard.stats.total_lessons') ?? 'Total Lessons', Lesson::query()->whereHas('section.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))->count())
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make(__('dashboard.stats.total_exams') ?? 'Total Exams', Exam::query()->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))->count())
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success'),
            Stat::make(__('dashboard.stats.total_tasks') ?? 'Total Tasks', Task::query()->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))->count())
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info'),
            Stat::make(__('dashboard.stats.pending_submissions') ?? 'Pending Submissions', TaskSubmission::query()->whereHas('task.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))->where('status', 'pending')->count())
                ->icon('heroicon-o-paper-clip')
                ->color('warning'),
        ];
    }
}
