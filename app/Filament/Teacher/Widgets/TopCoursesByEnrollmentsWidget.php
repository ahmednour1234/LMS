<?php

namespace App\Filament\Teacher\Widgets;

use App\Domain\Training\Models\Course;
use App\Support\Helpers\MultilingualHelper;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCoursesByEnrollmentsWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return __('dashboard.tables.top_courses_by_enrollments') ?? 'Top 5 Courses by Enrollments';
    }

    protected function getTableQuery(): Builder
    {
        $teacherId = auth('teacher')->id();

        return Course::query()
            ->where('owner_teacher_id', $teacherId)
            ->withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
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
                ->color('success'),

            TextColumn::make('created_at')
                ->label(__('dashboard.tables.created_at'))
                ->dateTime()
                ->sortable(),
        ];
    }
}
