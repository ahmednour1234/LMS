<?php

namespace App\Filament\Teacher\Widgets;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Support\Helpers\MultilingualHelper;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class WorstCoursesWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return __('dashboard.tables.worst_courses') ?? 'Worst 5 Courses';
    }

    protected function getTableQuery(): Builder
    {
        $teacherId = auth('teacher')->id();
        $hasRatingColumn = Schema::hasColumn('courses', 'rating');

        if ($hasRatingColumn) {
            return Course::query()
                ->where('owner_teacher_id', $teacherId)
                ->where('rating', '<=', 2.5)
                ->withCount('enrollments')
                ->orderBy('rating', 'asc')
                ->limit(5);
        }

        $courses = Course::query()
            ->where('owner_teacher_id', $teacherId)
            ->get();

        $enrollments = Enrollment::query()
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->get()
            ->groupBy('course_id');

        $coursesWithMetrics = $courses->map(function ($course) use ($enrollments) {
            $courseEnrollments = $enrollments->get($course->id, collect());
            $completionRate = $courseEnrollments->isNotEmpty()
                ? ($courseEnrollments->avg('progress_percent') ?? 0)
                : 0;

            return [
                'course' => $course,
                'metric' => $completionRate,
            ];
        })->filter(fn ($item) => $item['metric'] < 30)
            ->sortBy('metric')
            ->take(5)
            ->pluck('course');

        $courseIds = $coursesWithMetrics->pluck('id')->toArray();

        if (empty($courseIds)) {
            $courseIds = [0];
        }

        return Course::query()
            ->whereIn('id', $courseIds)
            ->withCount('enrollments');
    }

    protected function getTableColumns(): array
    {
        $hasRatingColumn = Schema::hasColumn('courses', 'rating');

        $columns = [
            TextColumn::make('code')
                ->label(__('courses.code'))
                ->searchable()
                ->sortable(),

            TextColumn::make('name')
                ->label(__('courses.name'))
                ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                ->searchable()
                ->sortable(),

            TextColumn::make('enrollments_count')
                ->label(__('dashboard.tables.enrollments_count') ?? 'Enrollments')
                ->counts('enrollments')
                ->sortable()
                ->badge()
                ->color('warning'),
        ];

        if ($hasRatingColumn) {
            $columns[] = TextColumn::make('rating')
                ->label(__('dashboard.tables.rating') ?? 'Rating')
                ->sortable()
                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 1))
                ->badge()
                ->color(fn ($state) => ($state ?? 0) <= 2.5 ? 'danger' : 'warning');
        } else {
            $teacherId = auth('teacher')->id();
            $enrollments = Enrollment::query()
                ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
                ->get()
                ->groupBy('course_id');

            $columns[] = TextColumn::make('completion_rate')
                ->label(__('dashboard.tables.completion_rate') ?? 'Completion Rate')
                ->state(function ($record) use ($enrollments) {
                    $courseEnrollments = $enrollments->get($record->id, collect());
                    if ($courseEnrollments->isEmpty()) {
                        return 0;
                    }
                    return number_format($courseEnrollments->avg('progress_percent') ?? 0, 1);
                })
                ->suffix('%')
                ->badge()
                ->color(fn ($state) => ($state ?? 0) < 30 ? 'danger' : 'warning');
        }

        $columns[] = TextColumn::make('created_at')
            ->label(__('dashboard.tables.created_at'))
            ->dateTime()
            ->sortable();

        return $columns;
    }
}
