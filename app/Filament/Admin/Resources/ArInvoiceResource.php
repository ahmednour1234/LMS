<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\ArInvoice;
use App\Filament\Admin\Resources\ArInvoiceResource\Pages;
use App\Filament\Admin\Resources\ArInvoiceResource\RelationManagers;
use App\Filament\Concerns\HasTableExports;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArInvoiceResource extends Resource
{
    use HasTableExports;
    protected static ?string $model = ArInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('navigation.ar_invoices');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.ar_invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.ar_invoices');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('enrollment_id')
                    ->relationship('enrollment', 'id', fn ($query) => $query->with('student', 'course'))
                    ->getOptionLabelUsing(function ($value) {
                        $enrollment = \App\Domain\Enrollment\Models\Enrollment::with('student', 'course')->find($value);
                        return $enrollment ? $enrollment->student->name . ' - ' . (is_array($enrollment->course->name) ? ($enrollment->course->name[app()->getLocale()] ?? $enrollment->course->name['ar'] ?? '') : $enrollment->course->name) : '';
                    })
                    ->disabled()
                    ->dehydrated(false)
                    ->label(__('ar_invoices.enrollment')),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled()
                    ->dehydrated(false)
                    ->label(__('ar_invoices.user')),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->where('branch_id', $user->branch_id)
                        ->where('user_id', $user->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('ar_invoices.student')),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('ar_invoices.user')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('SAR')
                    ->sortable()
                    ->label(__('ar_invoices.total_amount')),
                Tables\Columns\TextColumn::make('due_amount')
                    ->money('SAR')
                    ->sortable()
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
                    ->sortable()
                    ->label(__('ar_invoices.issued_at')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->label(__('filters.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->label(__('filters.user')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => __('ar_invoices.status_options.open'),
                        'partial' => __('ar_invoices.status_options.partial'),
                        'paid' => __('ar_invoices.status_options.paid'),
                        'canceled' => __('ar_invoices.status_options.canceled'),
                    ])
                    ->label(__('ar_invoices.status')),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filters.date_from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '<=', $date)
                            );
                    })
                    ->label(__('filters.date_range')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print')
                    ->label(__('exports.print'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (ArInvoice $record) => route('invoices.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->headerActions(static::getExportActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArInvoices::route('/'),
            'view' => Pages\ViewArInvoice::route('/{record}'),
        ];
    }
}
