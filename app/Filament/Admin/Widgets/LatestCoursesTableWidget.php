<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Training\Models\Course;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class LatestCoursesTableWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public function getHeading(): string
    {
        return __('dashboard.tables.latest_courses');
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        return Course::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
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
                ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['en'] ?? '') : $state)
                ->searchable()
                ->sortable(),
            TextColumn::make('program.name')
                ->label(__('courses.program'))
                ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['en'] ?? 'N/A') : ($state ?? 'N/A'))
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

