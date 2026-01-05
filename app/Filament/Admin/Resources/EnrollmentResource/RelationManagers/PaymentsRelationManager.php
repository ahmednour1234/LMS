<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label(__('payments.amount')),
                Forms\Components\Select::make('method')
                    ->options([
                        'cash' => __('payments.method_options.cash'),
                        'bank' => __('payments.method_options.bank'),
                        'gateway' => __('payments.method_options.gateway'),
                    ])
                    ->default('cash')
                    ->required()
                    ->label(__('payments.method')),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => __('payments.status_options.pending'),
                        'paid' => __('payments.status_options.paid'),
                        'failed' => __('payments.status_options.failed'),
                        'refunded' => __('payments.status_options.refunded'),
                    ])
                    ->default('pending')
                    ->required()
                    ->label(__('payments.status')),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label(__('payments.paid_at')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->money('SAR')
                    ->sortable()
                    ->label(__('payments.amount')),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('payments.method_options.' . $state))
                    ->label(__('payments.method')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('payments.status_options.' . $state))
                    ->color(fn ($state) => match($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    })
                    ->label(__('payments.status')),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('payments.paid_at')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

