<?php

namespace App\Filament\Teacher\Pages\Reports;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Filament\Concerns\HasTableExports;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeacherRegistrationsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasTableExports;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static string $view = 'filament.teacher.pages.teacher-registrations-page';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 22;

    public array $stats = [
        'total' => 0,
        'total_amount' => '0.00',
        'paid_amount' => '0.00',
        'due_amount' => '0.00',
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

    protected function baseQuery(): Builder
    {
        $teacherId = auth('teacher')->id();

        return Enrollment::query()
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->with(['student', 'course'])
            ->withSum(['payments as paid_amount_sum' => fn ($q) => $q->where('status', 'completed')], 'amount');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->columns([
                TextColumn::make('reference')
                    ->label(__('enrollments.reference') ?: 'Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('student.name')
                    ->label(__('enrollments.student') ?: 'Student')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

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

                TextColumn::make('status')
                    ->label(__('enrollments.status') ?: 'Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.status_options.' . $state->value) ?? $state->value)
                    ->color(fn ($state) => match ($state->value) {
                        'active' => 'success',
                        'pending_payment' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label(__('dashboard.tables.created_at') ?: 'Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label(__('filters.date_from') ?: 'From Date')
                            ->native(false),
                        DatePicker::make('created_until')
                            ->label(__('filters.date_to') ?: 'To Date')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['created_from'])) $indicators[] = (__('filters.date_from') ?: 'From') . ': ' . $data['created_from'];
                        if (!empty($data['created_until'])) $indicators[] = (__('filters.date_to') ?: 'To') . ': ' . $data['created_until'];
                        return $indicators;
                    }),

                SelectFilter::make('course_id')
                    ->label(__('Course') ?: 'Course')
                    ->options(function () {
                        $teacherId = auth('teacher')->id();

                        return Course::query()
                            ->where('owner_teacher_id', $teacherId)
                            ->get()
                            ->mapWithKeys(function ($course) {
                                $name = is_array($course->name)
                                    ? ($course->name[app()->getLocale()] ?? $course->name['en'] ?? $course->name['ar'] ?? '')
                                    : $course->name;

                                return [$course->id => $name];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_status')
                    ->label(__('Payment Status') ?: 'Payment Status')
                    ->options([
                        'completed' => __('Completed') ?: 'Completed',
                        'partial' => __('Partial') ?: 'Partial',
                        'pending' => __('Pending') ?: 'Pending',
                    ])
                    ->query(function (Builder $query, array $state): Builder {
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
            ->paginated([10, 25, 50])
            ->deferLoading()
            ->filtersTriggerAction(fn ($action) => $action->button()->label(__('filters.title') ?: 'Filters')->icon('heroicon-o-funnel'))
            ->emptyStateHeading(__('reports.no_data') ?: 'No registrations yet')
            ->emptyStateDescription(__('reports.no_data_hint') ?: 'Try adjusting filters or select a different date range.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->modifyQueryUsing(function (Builder $query) {
                $this->recalculateStatsFromQuery($query);
                return $query;
            });
    }

    protected function recalculateStats(): void
    {
        $this->recalculateStatsFromQuery($this->baseQuery());
    }

    protected function recalculateStatsFromQuery(Builder $query): void
    {
        $total = (int) (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('total_amount');
        $enrollmentIds = (clone $query)->pluck('id');
        $paidAmount = $enrollmentIds->isEmpty() ? 0 : (float) DB::table('payments')
            ->whereIn('enrollment_id', $enrollmentIds)
            ->where('status', 'completed')
            ->sum('amount');
        $dueAmount = max($totalAmount - $paidAmount, 0);

        $this->stats = [
            'total' => $total,
            'total_amount' => number_format($totalAmount, 2),
            'paid_amount' => number_format($paidAmount, 2),
            'due_amount' => number_format($dueAmount, 2),
        ];
    }
}
