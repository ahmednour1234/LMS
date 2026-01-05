<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ArInvoiceRelationManager extends RelationManager
{
    protected static string $relationship = 'arInvoice';

    protected static ?string $title = 'AR Invoice';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('ar_invoices.invoice_details'))
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label(__('ar_invoices.user'))
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->label(__('ar_invoices.total_amount')),
                        Forms\Components\TextInput::make('due_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                return $record ? $record->due_amount : 0;
                            })
                            ->label(__('ar_invoices.due_amount')),
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => __('ar_invoices.status_options.open'),
                                'partial' => __('ar_invoices.status_options.partial'),
                                'paid' => __('ar_invoices.status_options.paid'),
                                'canceled' => __('ar_invoices.status_options.canceled'),
                            ])
                            ->disabled()
                            ->dehydrated(false)
                            ->label(__('ar_invoices.status')),
                        Forms\Components\DateTimePicker::make('issued_at')
                            ->disabled()
                            ->dehydrated(false)
                            ->label(__('ar_invoices.issued_at')),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('ar_invoices.user')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('SAR')
                    ->label(__('ar_invoices.total_amount')),
                Tables\Columns\TextColumn::make('due_amount')
                    ->money('SAR')
                    ->formatStateUsing(function ($record) {
                        return $record ? $record->due_amount : 0;
                    })
                    ->label(__('ar_invoices.due_amount')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('ar_invoices.status_options.' . $state))
                    ->color(fn ($state) => match($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->label(__('ar_invoices.status')),
                Tables\Columns\TextColumn::make('issued_at')
                    ->dateTime()
                    ->label(__('ar_invoices.issued_at')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action - invoices are auto-generated
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Admin\Resources\ArInvoiceResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('ar_invoices.no_invoice'))
            ->emptyStateDescription(__('ar_invoices.no_invoice_description'));
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}

