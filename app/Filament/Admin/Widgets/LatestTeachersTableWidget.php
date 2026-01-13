<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Training\Models\Teacher;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class LatestTeachersTableWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 9;

    public function getHeading(): string
    {
        return __('dashboard.tables.latest_teachers');
    }

    protected function getTableQuery(): Builder
    {
        return Teacher::query()
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('teachers.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->label(__('teachers.email'))
                ->searchable()
                ->sortable(),
            TextColumn::make('active')
                ->label(__('teachers.active'))
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

