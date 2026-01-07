<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\RelationManagers;

use App\Domain\Accounting\Models\PdfInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PdfInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'PDF Invoices';

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();
        $query = PdfInvoice::query()
            ->whereHas('payment', function ($query) {
                $query->where('enrollment_id', $this->ownerRecord->id);
            });
        
        if (!$user->isSuperAdmin()) {
            $query->whereHas('payment', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                    ->where('user_id', $user->id);
            });
        }
        
        return $query;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_no')
                    ->required()
                    ->maxLength(255)
                    ->label(__('pdf_invoices.invoice_no')),
                Forms\Components\DateTimePicker::make('issued_at')
                    ->required()
                    ->default(now())
                    ->label(__('pdf_invoices.issued_at')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('pdf_invoices.invoice'))
            ->pluralModelLabel(__('pdf_invoices.invoices'))
            ->recordTitleAttribute('invoice_no')
            ->columns([
                Tables\Columns\TextColumn::make('payment.amount')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('pdf_invoices.payment_amount')),
                Tables\Columns\TextColumn::make('invoice_no')
                    ->searchable()
                    ->sortable()
                    ->label(__('pdf_invoices.invoice_no')),
                Tables\Columns\TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('pdf_invoices.issued_at')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('branch_id')
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->options(function () {
                                return \App\Domain\Branch\Models\Branch::query()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->label(__('filters.branch')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['branch_id'],
                            fn (Builder $query, $branchId): Builder => $query->whereHas('payment', function ($q) use ($branchId) {
                                $q->where('branch_id', $branchId);
                            })
                        );
                    })
                    ->visible(fn () => auth()->user()->isSuperAdmin())
                    ->label(__('filters.branch')),
                Filter::make('user_id')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->options(function () {
                                return \App\Models\User::query()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->label(__('filters.user')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['user_id'],
                            fn (Builder $query, $userId): Builder => $query->whereHas('payment', function ($q) use ($userId) {
                                $q->where('user_id', $userId);
                            })
                        );
                    })
                    ->label(__('filters.user')),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}

