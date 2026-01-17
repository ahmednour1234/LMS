<?php

namespace App\Filament\Teacher\Widgets;

use App\Domain\Training\Models\Course;
use App\Support\Helpers\MultilingualHelper;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestCoursesTableWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return __('dashboard.tables.latest_courses') ?? 'Latest Courses';
    }

    protected function getTableQuery(): Builder
    {
        $teacherId = auth('teacher')->id();

        return Course::query()
            ->where('owner_teacher_id', $teacherId)
            ->latest()
            ->limit(10);
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
            TextColumn::make('program.name')
                ->label(__('courses.program'))
                ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state) ?: 'N/A')
                ->searchable()
                ->sortable(),
            TextColumn::make('is_active')
                ->label(__('courses.is_active'))
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? __('dashboard.status.active') : __('dashboard.status.inactive'))
                ->color(fn ($state) => $state ? 'success' : 'danger')
                ->sortable(),
            TextColumn::make('created_at')
                ->label(__('dashboard.tables.created_at'))
                ->dateTime()
                ->sortable(),
        ];
    }
}
