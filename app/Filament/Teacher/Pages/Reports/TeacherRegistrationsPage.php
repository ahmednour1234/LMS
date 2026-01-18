<?php

namespace App\Filament\Teacher\Pages\Reports;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Enums\PaymentStatus;
use App\Filament\Concerns\HasTableExports;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TeacherRegistrationsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasTableExports;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static string $view = 'filament.teacher.pages.teacher-registrations-page';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 22;

    public static function getNavigationLabel(): string
    {
        return __('navigation.enrollments') ?? 'Registrations';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.enrollments') ?? 'Registrations';
    }

    public function table(Table $table): Table
    {
        $teacherId = auth('teacher')->id();

        return $table
            ->query(
                Enrollment::query()
                    ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
                    ->with(['student', 'course', 'payments'])
            )
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.reference') ?? 'Reference'),

                TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.student') ?? 'Student'),

                TextColumn::make('course.name')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.course') ?? 'Course'),

                TextColumn::make('total_amount')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('enrollments.total_amount') ?? 'Total Amount'),

                TextColumn::make('paid_amount')
                    ->money('OMR')
                    ->state(function ($record) {
                        return $record->payments()->where('status', 'completed')->sum('amount');
                    })
                    ->label(__('enrollments.paid_amount') ?? 'Paid Amount'),

                TextColumn::make('due_amount')
                    ->money('OMR')
                    ->state(function ($record) {
                        $paid = $record->payments()->where('status', 'completed')->sum('amount');
                        return $record->total_amount - $paid;
                    })
                    ->label(__('enrollments.due_amount') ?? 'Due Amount'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.status_options.' . $state->value) ?? $state->value)
                    ->color(fn ($state) => match($state->value) {
                        'active' => 'success',
                        'pending_payment' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->label(__('enrollments.status') ?? 'Status'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('dashboard.tables.created_at') ?? 'Created At'),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label(__('filters.date_from') ?? 'From Date'),
                        DatePicker::make('created_until')
                            ->label(__('filters.date_to') ?? 'To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('course_id')
                    ->label(__('Course') ?? 'Course')
                    ->options(function () use ($teacherId) {
                        return Course::query()
                            ->where('owner_teacher_id', $teacherId)
                            ->get()
                            ->mapWithKeys(function ($course) {
                                $name = is_array($course->name) ? ($course->name['en'] ?? $course->name['ar'] ?? '') : $course->name;
                                return [$course->id => $name];
                            });
                    })
                    ->searchable(),

                SelectFilter::make('payment_status')
                    ->label(__('Payment Status') ?? 'Payment Status')
                    ->options([
                        'completed' => __('Completed') ?? 'Completed',
                        'partial' => __('Partial') ?? 'Partial',
                        'pending' => __('Pending') ?? 'Pending',
                    ])
                    ->query(function (Builder $query, $state): Builder {
                        if (!$state || !isset($state['value']) || !$state['value']) {
                            return $query;
                        }

                        $paidStatus = $state['value'];

                        return $query->where(function (Builder $q) use ($paidStatus) {
                            if ($paidStatus === 'completed') {
                                $q->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                ) >= enrollments.total_amount', ['completed']);
                            } elseif ($paidStatus === 'partial') {
                                $q->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                ) > 0', ['completed'])
                                ->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                ) < enrollments.total_amount', ['completed']);
                            } else {
                                $q->whereRaw('NOT EXISTS (
                                    SELECT 1 FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                )', ['completed']);
                            }
                        });
                    }),
            ])
            ->headerActions([
                ...static::getExportActions(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
