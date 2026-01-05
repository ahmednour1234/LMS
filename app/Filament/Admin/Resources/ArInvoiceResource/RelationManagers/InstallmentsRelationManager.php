<?php

namespace App\Filament\Admin\Resources\ArInvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'arInstallments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('installment_no')
                    ->numeric()
                    ->required()
                    ->label(__('installments.installment_no')),
                Forms\Components\DatePicker::make('due_date')
                    ->required()
                    ->label(__('installments.due_date')),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label(__('installments.amount')),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => __('installments.status_options.pending'),
                        'paid' => __('installments.status_options.paid'),
                        'overdue' => __('installments.status_options.overdue'),
                    ])
                    ->default('pending')
                    ->required()
                    ->label(__('installments.status')),
                Forms\Components\TextInput::make('paid_amount')
                    ->numeric()
                    ->default(0)
                    ->label(__('installments.paid_amount')),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label(__('installments.paid_at')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('installment_no')
            ->columns([
                Tables\Columns\TextColumn::make('installment_no')
                    ->numeric()
                    ->sortable()
                    ->label(__('installments.installment_no')),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label(__('installments.due_date'))
                    ->color(fn ($record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : null),
                Tables\Columns\TextColumn::make('amount')
                    ->money('SAR')
                    ->sortable()
                    ->label(__('installments.amount')),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('SAR')
                    ->label(__('installments.paid_amount')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('installments.status_options.' . $state))
                    ->color(fn ($state) => match($state) {
                        'paid' => 'success',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->label(__('installments.status')),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('installments.paid_at')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('installments.status_options.pending'),
                        'paid' => __('installments.status_options.paid'),
                        'overdue' => __('installments.status_options.overdue'),
                    ])
                    ->label(__('installments.status')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

