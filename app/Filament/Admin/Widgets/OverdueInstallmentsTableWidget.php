<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Accounting\Models\ArInstallment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class OverdueInstallmentsTableWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        return $table
            ->query(
                ArInstallment::query()
                    ->with(['arInvoice.enrollment.student', 'arInvoice.branch'])
                    ->when($branchId, function ($query) use ($branchId) {
                        return $query->whereHas('arInvoice', function ($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        });
                    })
                    ->where('due_date', '<', Carbon::today())
                    ->where('status', '!=', 'paid')
                    ->orderBy('due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('arInvoice.enrollment.student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('arInvoice.enrollment.reference')
                    ->label('Enrollment')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('installment_no')
                    ->label('Installment #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('arInvoice.branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_date', 'asc')
            ->paginated(false)
            ->heading('Overdue Installments');
    }
}

