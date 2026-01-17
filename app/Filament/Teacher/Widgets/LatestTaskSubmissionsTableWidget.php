<?php

namespace App\Filament\Teacher\Widgets;

use App\Domain\Training\Models\TaskSubmission;
use App\Support\Helpers\MultilingualHelper;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestTaskSubmissionsTableWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return __('dashboard.tables.latest_task_submissions') ?? 'Latest Task Submissions';
    }

    protected function getTableQuery(): Builder
    {
        $teacherId = auth('teacher')->id();

        return TaskSubmission::query()
            ->whereHas('task.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->with(['task', 'student'])
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('task.title')
                ->label(__('task_submissions.task'))
                ->formatStateUsing(fn ($state, $record) => $record?->task?->title ? MultilingualHelper::formatMultilingualField($record->task->title) : '')
                ->sortable(),
            TextColumn::make('student.student_code')
                ->label(__('task_submissions.student'))
                ->sortable(),
            TextColumn::make('score')
                ->label(__('task_submissions.score'))
                ->sortable(),
            TextColumn::make('status')
                ->label(__('task_submissions.status'))
                ->badge()
                ->formatStateUsing(fn ($state) => __('task_submissions.status_options.' . $state))
                ->color(fn ($state) => $state === 'reviewed' ? 'success' : 'warning'),
            TextColumn::make('created_at')
                ->label(__('dashboard.tables.submitted_at'))
                ->dateTime()
                ->sortable(),
        ];
    }
}
