<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\Payment;
use App\Filament\Admin\Resources\PaymentResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('navigation.payments');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.payments');
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
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state) {
                            $enrollment = \App\Domain\Enrollment\Models\Enrollment::with('student')->find($state);
                            if ($enrollment) {
                                $userId = $enrollment->user_id 
                                    ?? ($enrollment->student ? $enrollment->student->user_id : null);
                                if ($userId) {
                                    $set('user_id', $userId);
                                }
                            }
                        }
                    })
                    ->label(__('payments.enrollment')),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('payments.user')),
                Forms\Components\Select::make('installment_id')
                    ->relationship('installment', 'installment_no', fn ($query, $get) => 
                        $query->where('ar_invoice_id', 
                            \App\Domain\Enrollment\Models\Enrollment::find($get('enrollment_id'))?->arInvoice?->id
                        )
                    )
                    ->getOptionLabelUsing(fn ($value) => 'Installment #' . $value)
                    ->label(__('payments.installment')),
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
                Forms\Components\TextInput::make('gateway_ref')
                    ->label(__('payments.gateway_ref')),
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

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->where('branch_id', $user->branch_id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('payments.student')),
                Tables\Columns\TextColumn::make('enrollment.course.name')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : $state)
                    ->label(__('payments.course')),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('payments.status_options.pending'),
                        'paid' => __('payments.status_options.paid'),
                        'failed' => __('payments.status_options.failed'),
                        'refunded' => __('payments.status_options.refunded'),
                    ])
                    ->label(__('payments.status')),
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'cash' => __('payments.method_options.cash'),
                        'bank' => __('payments.method_options.bank'),
                        'gateway' => __('payments.method_options.gateway'),
                    ])
                    ->label(__('payments.method')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
