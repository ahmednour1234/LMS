<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\RelationManagers;

use App\Domain\Accounting\Models\PdfInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PdfInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'PDF Invoices';

    protected function getTableQuery(): Builder
    {
        return PdfInvoice::query()
            ->whereHas('payment', function ($query) {
                $query->where('enrollment_id', $this->ownerRecord->id);
            });
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
                    ->money('SAR')
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
                //
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

