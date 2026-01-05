<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Enrollment\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\EnrollmentResource\Pages;
use App\Filament\Admin\Resources\EnrollmentResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'enrollment';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('navigation.enrollments');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.enrollment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.enrollments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.enrollment');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('enrollments.student')),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('enrollments.user')),
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => {
                        $user = auth()->user();
                        $query = $query->where('is_active', true);
                        if (!$user->isSuperAdmin()) {
                            $query->where('branch_id', $user->branch_id);
                        }
                        return $query;
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $course = \App\Domain\Training\Models\Course::find($value);
                        if (!$course) return '';
                        $code = $course->code ?? '';
                        $name = is_array($course->name) ? ($course->name[app()->getLocale()] ?? $course->name['ar'] ?? '') : $course->name;
                        return $code . ' - ' . $name;
                    })
                    ->searchable(fn ($query, $search) => $query->where(function ($q) use ($search) {
                        $q->where('code', 'like', "%{$search}%")
                          ->orWhereJsonContains('name->ar', $search)
                          ->orWhereJsonContains('name->en', $search);
                    }))
                    ->preload()
                    ->required()
                    ->label(__('enrollments.course')),
                Forms\Components\Select::make('pricing_type')
                    ->options([
                        'full' => __('enrollments.pricing_type_options.full'),
                        'installment' => __('enrollments.pricing_type_options.installment'),
                    ])
                    ->default('full')
                    ->required()
                    ->label(__('enrollments.pricing_type')),
                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->label(__('enrollments.total_amount')),
                Forms\Components\TextInput::make('progress_percent')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->label(__('enrollments.progress_percent')),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => __('enrollments.status_options.pending'),
                        'active' => __('enrollments.status_options.active'),
                        'completed' => __('enrollments.status_options.completed'),
                        'cancelled' => __('enrollments.status_options.cancelled'),
                    ])
                    ->default('pending')
                    ->required()
                    ->label(__('enrollments.status')),
                Forms\Components\DateTimePicker::make('started_at')
                    ->label(__('enrollments.started_at')),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label(__('enrollments.completed_at')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('enrollments.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
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
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.student')),
                Tables\Columns\TextColumn::make('course.name')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : $state)
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.course')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('SAR')
                    ->sortable()
                    ->label(__('enrollments.total_amount')),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('SAR')
                    ->default(function ($record) {
                        return $record->payments()->where('status', 'paid')->sum('amount');
                    })
                    ->label(__('enrollments.paid_amount')),
                Tables\Columns\TextColumn::make('due_amount')
                    ->money('SAR')
                    ->default(function ($record) {
                        $paid = $record->payments()->where('status', 'paid')->sum('amount');
                        return $record->total_amount - $paid;
                    })
                    ->label(__('enrollments.due_amount')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.status_options.' . $state->value))
                    ->color(fn ($state) => match($state) {
                        EnrollmentStatus::ACTIVE => 'success',
                        EnrollmentStatus::COMPLETED => 'info',
                        EnrollmentStatus::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->label(__('enrollments.status')),
                Tables\Columns\TextColumn::make('progress_percent')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->sortable()
                    ->label(__('enrollments.progress_percent')),
                Tables\Columns\TextColumn::make('pricing_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.pricing_type_options.' . $state))
                    ->label(__('enrollments.pricing_type')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('enrollments.status_options.pending'),
                        'active' => __('enrollments.status_options.active'),
                        'completed' => __('enrollments.status_options.completed'),
                        'cancelled' => __('enrollments.status_options.cancelled'),
                    ])
                    ->label(__('enrollments.status')),
                Tables\Filters\SelectFilter::make('pricing_type')
                    ->options([
                        'full' => __('enrollments.pricing_type_options.full'),
                        'installment' => __('enrollments.pricing_type_options.installment'),
                    ])
                    ->label(__('enrollments.pricing_type')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('record_payment')
                    ->label(__('enrollments.actions.record_payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('installment_id')
                            ->relationship('arInvoice.arInstallments', 'installment_no', fn ($query, $get) => 
                                $query->where('status', '!=', 'paid')
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
                    ])
                    ->action(function (Enrollment $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $payment = $record->payments()->create([
                                'user_id' => $record->user_id,
                                'branch_id' => $record->branch_id,
                                'installment_id' => $data['installment_id'] ?? null,
                                'amount' => $data['amount'],
                                'method' => $data['method'],
                                'gateway_ref' => $data['gateway_ref'] ?? null,
                                'status' => 'paid',
                                'paid_at' => now(),
                                'created_by' => auth()->id(),
                            ]);
                            
                            event(new \App\Domain\Accounting\Events\PaymentPaid($payment));
                        });
                    }),
                Tables\Actions\Action::make('mark_completed')
                    ->label(__('enrollments.actions.mark_completed'))
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Enrollment $record) {
                        DB::transaction(function () use ($record) {
                            $record->update([
                                'status' => EnrollmentStatus::COMPLETED,
                                'completed_at' => now(),
                                'progress_percent' => 100,
                            ]);
                            
                            event(new \App\Domain\Enrollment\Events\EnrollmentCompleted($record));
                        });
                    })
                    ->visible(fn ($record) => $record->status !== EnrollmentStatus::COMPLETED),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\PdfInvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'view' => Pages\ViewEnrollment::route('/{record}'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
