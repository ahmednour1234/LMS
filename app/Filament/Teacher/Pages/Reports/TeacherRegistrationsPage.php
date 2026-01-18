<?php

namespace App\Filament\Teacher\Pages\Reports;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Filament\Concerns\HasTableExports;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;

class TeacherRegistrationsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasTableExports;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static string $view = 'filament.teacher.pages.teacher-registrations-page';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 22;

    public array $stats = [
        'total' => 0,
        'total_amount' => '0',
        'paid_amount' => '0',
        'due_amount' => '0',
    ];

    public static function getNavigationLabel(): string
    {
        return __('navigation.enrollments') ?: 'Registrations';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.enrollments') ?: 'Registrations';
    }

    public function mount(): void
    {
        $this->recalculateStats();
    }

    protected function getBaseQuery(): Builder
    {
        $teacherId = auth('teacher')->id();

        return Enrollment::query()
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->with(['student', 'course'])
            ->withSum(['payments as paid_amount_sum' => function ($q) {
                $q->where('status', 'completed');
            }], 'amount');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBaseQuery())
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.reference') ?: 'Reference')
                    ->copyable()
                    ->toggleable(),

                ViewColumn::make('student.name')
                    ->label(__('enrollments.student') ?: 'Student')
                    ->view('filament.teacher.tables.columns.student-with-avatar'),

                TextColumn::make('course.name')
                    ->label(__('enrollments.course') ?: 'Course')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label(__('enrollments.total_amount') ?: 'Total Amount')
                    ->money('OMR')
                    ->sortable(),

                TextColumn::make('paid_amount_sum')
                    ->label(__('enrollments.paid_amount') ?: 'Paid Amount')
                    ->money('OMR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('due_amount_calc')
                    ->label(__('enrollments.due_amount') ?: 'Due Amount')
                    ->state(function ($record) {
                        $paid = (float) ($record->paid_amount_sum ?? 0);
                        return max(((float) $record->total_amount) - $paid, 0);
                    })
                    ->money('OMR')
                    ->color(fn ($state) => ((float) $state) > 0 ? 'warning' : 'success'),

                ViewColumn::make('payment_progress')
                    ->label(__('Payment') ?: 'Payment')
                    ->view('filament.teacher.tables.columns.payment-progress'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.status_options.' . $state->value) ?? $state->value)
                    ->color(fn ($state) => match ($state->value) {
                        'active' => 'success',
                        'pending_payment' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->label(__('enrollments.status') ?: 'Status'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('dashboard.tables.created_at') ?: 'Created At'),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('filters.date_from') ?: 'From Date')->native(false),
                        DatePicker::make('created_until')->label(__('filters.date_to') ?: 'To Date')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['created_from'])) $indicators[] = 'From: ' . $data['created_from'];
                        if (!empty($data['created_until'])) $indicators[] = 'To: ' . $data['created_until'];
                        return $indicators;
                    }),

                SelectFilter::make('course_id')
                    ->label(__('Course') ?: 'Course')
                    ->options(fn () => Course::query()
                        ->where('owner_teacher_id', auth('teacher')->id())
                        ->get()
                        ->mapWithKeys(function ($course) {
                            $name = is_array($course->name)
                                ? ($course->name[app()->getLocale()] ?? $course->name['en'] ?? $course->name['ar'] ?? '')
                                : $course->name;
                            return [$course->id => $name];
                        })
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_status')
                    ->label(__('Payment Status') ?: 'Payment Status')
                    ->options([
                        'completed' => __('Completed') ?: 'Completed',
                        'partial' => __('Partial') ?: 'Partial',
                        'pending' => __('Pending') ?: 'Pending',
                    ])
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (!$value) return $query;

                        return $query->where(function (Builder $q) use ($value) {
                            if ($value === 'completed') {
                                $q->whereRaw('COALESCE(paid_amount_sum, 0) >= enrollments.total_amount');
                            } elseif ($value === 'partial') {
                                $q->whereRaw('COALESCE(paid_amount_sum, 0) > 0')
                                  ->whereRaw('COALESCE(paid_amount_sum, 0) < enrollments.total_amount');
                            } else {
                                $q->whereRaw('COALESCE(paid_amount_sum, 0) = 0');
                            }
                        });
                    }),
            ])
            ->headerActions([
                ...static::getExportActions(),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->filtersTriggerAction(
                fn ($action) => $action->button()->label(__('filters.title') ?: 'Filters')->icon('heroicon-o-funnel')
            )
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()
                    ->label(__('general.view') ?: 'View')
                    ->icon('heroicon-o-eye'),
            ])
            ->recordUrl(null)
            ->emptyStateHeading(__('reports.no_data') ?: 'No registrations yet')
            ->emptyStateDescription(__('reports.no_data_hint') ?: 'Try adjusting filters or select a different date range.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->tap(fn () => $this->recalculateStatsFromQuery($query));
            });
    }

    protected function recalculateStats(): void
    {
        $query = $this->getBaseQuery();
        $this->recalculateStatsFromQuery($query);
    }

    protected function recalculateStatsFromQuery(Builder $query): void
    {
        $cloned = clone $query;

        $total = (int) $cloned->count();
        $totalAmount = (float) (clone $query)->sum('total_amount');
        $paidAmount = (float) (clone $query)->sum('paid_amount_sum');
        $dueAmount = max($totalAmount - $paidAmount, 0);

        $this->stats = [
            'total' => $total,
            'total_amount' => number_format($totalAmount, 2),
            'paid_amount' => number_format($paidAmount, 2),
            'due_amount' => number_format($dueAmount, 2),
        ];
    }
}
