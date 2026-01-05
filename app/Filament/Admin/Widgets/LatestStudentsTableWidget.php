<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Enrollment\Models\Student;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class LatestStudentsTableWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 8;

    public function getHeading(): string
    {
        return __('dashboard.tables.latest_students');
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        return Student::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('student_code')
                ->label(__('students.student_code'))
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->label(__('students.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->label(__('students.email'))
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->label(__('students.status'))
                ->badge()
                ->formatStateUsing(fn ($state) => __('students.status_options.' . $state))
                ->color(fn ($state) => match($state) {
                    'active' => 'success',
                    'inactive' => 'gray',
                    'suspended' => 'danger',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('created_at')
                ->label(__('dashboard.tables.created_at'))
                ->dateTime()
                ->sortable(),
        ];
    }
}

