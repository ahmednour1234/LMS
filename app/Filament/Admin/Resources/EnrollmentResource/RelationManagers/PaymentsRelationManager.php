<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\RelationManagers;

use App\Enums\PaymentStatus;
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
                        PaymentStatus::PENDING->value => __('payments.status_options.pending'),
                        PaymentStatus::COMPLETED->value => __('payments.status_options.paid'),
                        PaymentStatus::FAILED->value => __('payments.status_options.failed'),
                    ])
                    ->default(PaymentStatus::PENDING->value)
                    ->required()
                    ->label(__('payments.status'))
                    ->afterStateUpdated(function ($state, $record, Forms\Set $set) {
                        if ($state === PaymentStatus::COMPLETED->value && !$record?->paid_at) {
                            $set('paid_at', now());
                        } elseif ($state !== PaymentStatus::COMPLETED->value) {
                            $set('paid_at', null);
                        }
                    }),
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
                    ->money('OMR')
                    ->sortable()
                    ->label(__('payments.amount')),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('payments.method_options.' . $state))
                    ->label(__('payments.method')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        PaymentStatus::COMPLETED->value => __('payments.status_options.paid'),
                        PaymentStatus::FAILED->value => __('payments.status_options.failed'),
                        PaymentStatus::PENDING->value => __('payments.status_options.pending'),
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        PaymentStatus::COMPLETED->value => 'success',
                        PaymentStatus::FAILED->value => 'danger',
                        PaymentStatus::PENDING->value => 'gray',
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
                Tables\Actions\EditAction::make()
                    ->after(function ($record, array $data) {
                        $enrollment = $record->enrollment;
                        if ($enrollment) {
                            $enrollment->touch();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        $enrollment = $record->enrollment;
                        if ($enrollment) {
                            $enrollment->touch();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

