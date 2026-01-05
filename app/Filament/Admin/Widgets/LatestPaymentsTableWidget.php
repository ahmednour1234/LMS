<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Accounting\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class LatestPaymentsTableWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        return $table
            ->query(
                Payment::query()
                    ->with(['enrollment.student', 'branch'])
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->where('status', 'completed')
                    ->orderBy('paid_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('paid_at', 'desc')
            ->paginated(false)
            ->heading('Latest Payments');
    }
}

