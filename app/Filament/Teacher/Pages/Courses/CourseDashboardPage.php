<?php

namespace App\Filament\Teacher\Pages\Courses;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use App\Domain\Training\Models\CourseSession;
use App\Domain\Training\Models\CourseSessionAttendance;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Concerns\HasTableExports;
use App\Services\PdfService;
use App\Services\TableExportService;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CourseDashboardPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasTableExports;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.teacher.pages.courses.course-dashboard';

    public Course $record;

    public ?string $activeTab = 'overview';

    protected ?string $heading = null;

    protected ?string $subheading = null;

    public function mount(int | string $record): void
    {
        $course = Course::findOrFail($record);
        abort_unless($course->owner_teacher_id === auth('teacher')->id(), 404);

        $this->record = $course;
        $this->heading = MultilingualHelper::formatMultilingualField($course->name);
        $this->subheading = __('course_dashboard.subtitle', ['code' => $course->code]) ?? 'Course Dashboard';
    }

    protected static ?string $slug = 'courses/{record}/dashboard';

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $record = $parameters['record'] ?? null;
        if (!$record) {
            throw new \InvalidArgumentException('Record parameter is required');
        }

        $recordId = $record instanceof \Illuminate\Database\Eloquent\Model ? $record->id : $record;
        
        return url('/teacher-admin/courses/' . $recordId . '/dashboard');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('course_dashboard.back_to_courses') ?? 'Back to Courses')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => \App\Filament\Teacher\Resources\Training\CourseResource::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return $this->heading ?? __('course_dashboard.title') ?? 'Course Dashboard';
    }

    public function updatedActiveTab(): void
    {
        // Reset table state when switching tabs
    }

    public function registrationsTable(Table $table): Table
    {
        return $table
            ->query(
                Enrollment::query()
                    ->where('course_id', $this->record->id)
                    ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                    ->with(['student', 'course', 'payments', 'arInvoice'])
            )
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.reference')),

                TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.student_name')),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.status_options.' . $state->value) ?? $state->value)
                    ->color(fn ($state) => match($state) {
                        EnrollmentStatus::ACTIVE => 'success',
                        EnrollmentStatus::PENDING_PAYMENT => 'warning',
                        EnrollmentStatus::COMPLETED => 'info',
                        EnrollmentStatus::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.status')),

                TextColumn::make('total_amount')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('course_dashboard.total_amount')),

                TextColumn::make('paid_amount')
                    ->money('OMR')
                    ->state(function ($record) {
                        return $record->payments()->where('status', PaymentStatus::COMPLETED->value)->sum('amount');
                    })
                    ->label(__('course_dashboard.paid_amount')),

                TextColumn::make('due_amount')
                    ->money('OMR')
                    ->state(function ($record) {
                        $paid = $record->payments()->where('status', PaymentStatus::COMPLETED->value)->sum('amount');
                        return max(0, $record->total_amount - $paid);
                    })
                    ->label(__('course_dashboard.due_amount')),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_dashboard.created_at')),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label(__('filters.date_from')),
                        DatePicker::make('created_until')
                            ->label(__('filters.date_to')),
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

                SelectFilter::make('payment_status')
                    ->label(__('course_dashboard.payment_status'))
                    ->options([
                        'completed' => __('course_dashboard.payment_status_completed'),
                        'partial' => __('course_dashboard.payment_status_partial'),
                        'pending' => __('course_dashboard.payment_status_pending'),
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
                                ) >= enrollments.total_amount', [PaymentStatus::COMPLETED->value]);
                            } elseif ($paidStatus === 'partial') {
                                $q->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                ) > 0', [PaymentStatus::COMPLETED->value])
                                ->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                ) < enrollments.total_amount', [PaymentStatus::COMPLETED->value]);
                            } else {
                                $q->whereRaw('NOT EXISTS (
                                    SELECT 1 FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                )', [PaymentStatus::COMPLETED->value]);
                            }
                        });
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('course_dashboard.view'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => __('course_dashboard.enrollment_details'))
                    ->modalContent(fn ($record) => view('filament.teacher.modals.enrollment-details', ['enrollment' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('course_dashboard.close')),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label(__('course_dashboard.export_excel'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $query = Enrollment::query()
                            ->where('course_id', $this->record->id)
                            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                            ->with(['student', 'payments']);

                        $columns = collect([
                            ['name' => 'reference', 'label' => __('course_dashboard.reference')],
                            ['name' => 'student.name', 'label' => __('course_dashboard.student_name')],
                            ['name' => 'status', 'label' => __('course_dashboard.status')],
                            ['name' => 'total_amount', 'label' => __('course_dashboard.total_amount')],
                            ['name' => 'paid_amount', 'label' => __('course_dashboard.paid_amount')],
                            ['name' => 'due_amount', 'label' => __('course_dashboard.due_amount')],
                            ['name' => 'created_at', 'label' => __('course_dashboard.created_at')],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_registrations_' . now()->format('Y-m-d');

                        return $service->exportXlsxFromCached($query->get(), $columns, $filename);
                    }),

                Action::make('exportPdf')
                    ->label(__('course_dashboard.export_pdf'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $query = Enrollment::query()
                            ->where('course_id', $this->record->id)
                            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                            ->with(['student', 'payments']);

                        $columns = collect([
                            ['name' => 'reference', 'label' => __('course_dashboard.reference')],
                            ['name' => 'student.name', 'label' => __('course_dashboard.student_name')],
                            ['name' => 'status', 'label' => __('course_dashboard.status')],
                            ['name' => 'total_amount', 'label' => __('course_dashboard.total_amount')],
                            ['name' => 'paid_amount', 'label' => __('course_dashboard.paid_amount')],
                            ['name' => 'due_amount', 'label' => __('course_dashboard.due_amount')],
                            ['name' => 'created_at', 'label' => __('course_dashboard.created_at')],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_registrations_' . now()->format('Y-m-d');

                        return $service->exportPdfFromCached($query->get(), $columns, $filename, __('course_dashboard.registrations_report'));
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function tasksTable(Table $table): Table
    {
        return $table
            ->query(
                TaskSubmission::query()
                    ->whereHas('task', fn (Builder $q) => 
                        $q->where('course_id', $this->record->id)
                          ->whereHas('course', fn (Builder $q2) => 
                              $q2->where('owner_teacher_id', auth('teacher')->id())
                          )
                    )
                    ->with(['task', 'student', 'mediaFile'])
            )
            ->columns([
                TextColumn::make('task.title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.task_title')),

                TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.student_name')),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('course_dashboard.submitted_at')),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('course_dashboard.submission_status.' . $state) ?? $state)
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'reviewed' => 'success',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.status')),

                TextColumn::make('score')
                    ->formatStateUsing(fn ($state, $record) => $state !== null ? number_format($state, 2) . ' / ' . ($record->task->max_score ?? 0) : '-')
                    ->label(__('course_dashboard.score')),

                TextColumn::make('task.due_date')
                    ->dateTime()
                    ->label(__('course_dashboard.due_date')),
            ])
            ->filters([
                SelectFilter::make('task_id')
                    ->label(__('course_dashboard.filter_by_task'))
                    ->options(function () {
                        return Task::query()
                            ->where('course_id', $this->record->id)
                            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                            ->get()
                            ->mapWithKeys(function ($task) {
                                $title = is_array($task->title) ? MultilingualHelper::formatMultilingualField($task->title) : $task->title;
                                return [$task->id => $title];
                            });
                    })
                    ->query(function (Builder $query, $state): Builder {
                        if (!$state || !isset($state['value']) || !$state['value']) {
                            return $query;
                        }
                        return $query->where('task_id', $state['value']);
                    }),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label(__('filters.date_from')),
                        DatePicker::make('created_until')
                            ->label(__('filters.date_to')),
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

                SelectFilter::make('status')
                    ->label(__('course_dashboard.submission_status_label'))
                    ->options([
                        'pending' => __('course_dashboard.submission_status.pending'),
                        'reviewed' => __('course_dashboard.submission_status.reviewed'),
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('course_dashboard.view_submission'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->mediaFile ? \Illuminate\Support\Facades\Storage::url($record->mediaFile->path) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->mediaFile !== null),

                Action::make('grade')
                    ->label(__('course_dashboard.grade'))
                    ->icon('heroicon-o-star')
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->numeric()
                            ->label(__('course_dashboard.score'))
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn ($record) => $record->task->max_score ?? 100),

                        Forms\Components\Textarea::make('feedback')
                            ->label(__('course_dashboard.feedback'))
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'score' => $data['score'],
                            'feedback' => $data['feedback'] ?? null,
                            'reviewed_at' => now(),
                            'reviewed_by' => auth('teacher')->id(),
                            'status' => 'reviewed',
                        ]);

                        Notification::make()
                            ->title(__('course_dashboard.graded_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label(__('course_dashboard.export_excel'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $query = TaskSubmission::query()
                            ->whereHas('task', fn (Builder $q) => 
                                $q->where('course_id', $this->record->id)
                                  ->whereHas('course', fn (Builder $q2) => 
                                      $q2->where('owner_teacher_id', auth('teacher')->id())
                                  )
                            )
                            ->with(['task', 'student']);

                        $columns = collect([
                            ['name' => 'task.title', 'label' => __('course_dashboard.task_title')],
                            ['name' => 'student.name', 'label' => __('course_dashboard.student_name')],
                            ['name' => 'created_at', 'label' => __('course_dashboard.submitted_at')],
                            ['name' => 'status', 'label' => __('course_dashboard.status')],
                            ['name' => 'score', 'label' => __('course_dashboard.score')],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_task_submissions_' . now()->format('Y-m-d');

                        return $service->exportXlsxFromCached($query->get(), $columns, $filename);
                    }),

                Action::make('exportPdf')
                    ->label(__('course_dashboard.export_pdf'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $query = TaskSubmission::query()
                            ->whereHas('task', fn (Builder $q) => 
                                $q->where('course_id', $this->record->id)
                                  ->whereHas('course', fn (Builder $q2) => 
                                      $q2->where('owner_teacher_id', auth('teacher')->id())
                                  )
                            )
                            ->with(['task', 'student']);

                        $columns = collect([
                            ['name' => 'task.title', 'label' => __('course_dashboard.task_title')],
                            ['name' => 'student.name', 'label' => __('course_dashboard.student_name')],
                            ['name' => 'created_at', 'label' => __('course_dashboard.submitted_at')],
                            ['name' => 'status', 'label' => __('course_dashboard.status')],
                            ['name' => 'score', 'label' => __('course_dashboard.score')],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_task_submissions_' . now()->format('Y-m-d');

                        return $service->exportPdfFromCached($query->get(), $columns, $filename, __('course_dashboard.task_submissions_report'));
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function attendanceTable(Table $table): Table
    {
        return $table
            ->query(
                CourseSessionAttendance::query()
                    ->whereHas('session', fn (Builder $q) => 
                        $q->where('course_id', $this->record->id)
                          ->whereHas('course', fn (Builder $q2) => 
                              $q2->where('owner_teacher_id', auth('teacher')->id())
                          )
                    )
                    ->with(['session', 'enrollment.student'])
            )
            ->columns([
                TextColumn::make('session.starts_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_dashboard.session_date')),

                TextColumn::make('enrollment.student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.student_name')),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('course_dashboard.attendance_status.' . ($state instanceof \App\Domain\Training\Enums\AttendanceStatus ? $state->value : $state)) ?? ($state instanceof \App\Domain\Training\Enums\AttendanceStatus ? $state->value : $state))
                    ->color(fn ($state) => match($state instanceof \App\Domain\Training\Enums\AttendanceStatus ? $state->value : $state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'excused' => 'info',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.status')),

                TextColumn::make('note')
                    ->limit(50)
                    ->label(__('course_dashboard.notes')),

                TextColumn::make('marked_at')
                    ->dateTime()
                    ->label(__('course_dashboard.marked_at')),
            ])
            ->filters([
                SelectFilter::make('session_id')
                    ->label(__('course_dashboard.filter_by_session'))
                    ->options(function () {
                        return CourseSession::query()
                            ->where('course_id', $this->record->id)
                            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                            ->get()
                            ->mapWithKeys(function ($session) {
                                return [$session->id => $session->starts_at?->format('Y-m-d H:i') ?? 'Session #' . $session->id];
                            });
                    })
                    ->query(function (Builder $query, $state): Builder {
                        if (!$state || !isset($state['value']) || !$state['value']) {
                            return $query;
                        }
                        return $query->where('session_id', $state['value']);
                    }),

                Filter::make('marked_at')
                    ->form([
                        DatePicker::make('marked_from')
                            ->label(__('filters.date_from')),
                        DatePicker::make('marked_until')
                            ->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['marked_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('marked_at', '>=', $date),
                            )
                            ->when(
                                $data['marked_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('marked_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('status')
                    ->label(__('course_dashboard.attendance_status_label'))
                    ->options([
                        'present' => __('course_dashboard.attendance_status.present'),
                        'absent' => __('course_dashboard.attendance_status.absent'),
                        'late' => __('course_dashboard.attendance_status.late'),
                        'excused' => __('course_dashboard.attendance_status.excused'),
                    ])
                    ->query(function (Builder $query, $state): Builder {
                        if (!$state || !isset($state['value']) || !$state['value']) {
                            return $query;
                        }
                        return $query->where('status', $state['value']);
                    }),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label(__('course_dashboard.export_excel'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $query = CourseSessionAttendance::query()
                            ->whereHas('session', fn (Builder $q) => 
                                $q->where('course_id', $this->record->id)
                                  ->whereHas('course', fn (Builder $q2) => 
                                      $q2->where('owner_teacher_id', auth('teacher')->id())
                                  )
                            )
                            ->with(['session', 'enrollment.student']);

                        $columns = collect([
                            ['name' => 'session.starts_at', 'label' => __('course_dashboard.session_date')],
                            ['name' => 'enrollment.student.name', 'label' => __('course_dashboard.student_name')],
                            ['name' => 'status', 'label' => __('course_dashboard.status')],
                            ['name' => 'note', 'label' => __('course_dashboard.notes')],
                            ['name' => 'marked_at', 'label' => __('course_dashboard.marked_at')],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_attendance_' . now()->format('Y-m-d');

                        return $service->exportXlsxFromCached($query->get(), $columns, $filename);
                    }),

                Action::make('exportPdf')
                    ->label(__('course_dashboard.export_pdf'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $query = CourseSessionAttendance::query()
                            ->whereHas('session', fn (Builder $q) => 
                                $q->where('course_id', $this->record->id)
                                  ->whereHas('course', fn (Builder $q2) => 
                                      $q2->where('owner_teacher_id', auth('teacher')->id())
                                  )
                            )
                            ->with(['session', 'enrollment.student']);

                        $columns = collect([
                            ['name' => 'session.starts_at', 'label' => __('course_dashboard.session_date')],
                            ['name' => 'enrollment.student.name', 'label' => __('course_dashboard.student_name')],
                            ['name' => 'status', 'label' => __('course_dashboard.status')],
                            ['name' => 'note', 'label' => __('course_dashboard.notes')],
                            ['name' => 'marked_at', 'label' => __('course_dashboard.marked_at')],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_attendance_' . now()->format('Y-m-d');

                        return $service->exportPdfFromCached($query->get(), $columns, $filename, __('course_dashboard.attendance_report'));
                    }),
            ])
            ->defaultSort('session.starts_at', 'desc');
    }

    public function exportCourseReportPdf()
    {
        $course = $this->record;
        $stats = $this->getOverviewStats();

        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
            ->with(['student', 'payments'])
            ->get();

        $taskSubmissions = TaskSubmission::query()
            ->whereHas('task', fn (Builder $q) => 
                $q->where('course_id', $course->id)
                  ->whereHas('course', fn (Builder $q2) => $q2->where('owner_teacher_id', auth('teacher')->id()))
            )
            ->with(['task', 'student'])
            ->get();

        $attendance = CourseSessionAttendance::query()
            ->whereHas('session', fn (Builder $q) => 
                $q->where('course_id', $course->id)
                  ->whereHas('course', fn (Builder $q2) => $q2->where('owner_teacher_id', auth('teacher')->id()))
            )
            ->with(['session', 'enrollment.student'])
            ->get();

        $pdfService = app(PdfService::class);
        return $pdfService->render('pdf.course-dashboard', [
            'course' => $course,
            'stats' => $stats,
            'enrollments' => $enrollments,
            'taskSubmissions' => $taskSubmissions,
            'attendance' => $attendance,
        ]);
    }

    public function exportCourseReportExcel()
    {
        return $this->exportRegistrationsExcel();
    }

    public function exportRegistrationsExcel()
    {
        $query = Enrollment::query()
            ->where('course_id', $this->record->id)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
            ->with(['student', 'payments']);

        $columns = collect([
            ['name' => 'reference', 'label' => __('course_dashboard.reference') ?? 'Reference'],
            ['name' => 'student.name', 'label' => __('course_dashboard.student_name') ?? 'Student'],
            ['name' => 'status', 'label' => __('course_dashboard.status') ?? 'Status'],
            ['name' => 'total_amount', 'label' => __('course_dashboard.total_amount') ?? 'Total'],
            ['name' => 'paid_amount', 'label' => __('course_dashboard.paid_amount') ?? 'Paid'],
            ['name' => 'due_amount', 'label' => __('course_dashboard.due_amount') ?? 'Due'],
            ['name' => 'created_at', 'label' => __('course_dashboard.created_at') ?? 'Created At'],
        ]);

        $service = app(TableExportService::class);
        $filename = 'course_' . $this->record->code . '_registrations_' . now()->format('Y-m-d');

        return $service->exportXlsxFromCached($query->get(), $columns, $filename);
    }

    public function exportTasksExcel()
    {
        $query = TaskSubmission::query()
            ->whereHas('task', fn (Builder $q) => 
                $q->where('course_id', $this->record->id)
                  ->whereHas('course', fn (Builder $q2) => $q2->where('owner_teacher_id', auth('teacher')->id()))
            )
            ->with(['task', 'student']);

        $columns = collect([
            ['name' => 'task.title', 'label' => __('course_dashboard.task_title') ?? 'Task'],
            ['name' => 'student.name', 'label' => __('course_dashboard.student_name') ?? 'Student'],
            ['name' => 'created_at', 'label' => __('course_dashboard.submitted_at') ?? 'Submitted'],
            ['name' => 'status', 'label' => __('course_dashboard.status') ?? 'Status'],
            ['name' => 'score', 'label' => __('course_dashboard.score') ?? 'Score'],
        ]);

        $service = app(TableExportService::class);
        $filename = 'course_' . $this->record->code . '_task_submissions_' . now()->format('Y-m-d');

        return $service->exportXlsxFromCached($query->get(), $columns, $filename);
    }

    public function exportAttendanceExcel()
    {
        $query = CourseSessionAttendance::query()
            ->whereHas('session', fn (Builder $q) => 
                $q->where('course_id', $this->record->id)
                  ->whereHas('course', fn (Builder $q2) => $q2->where('owner_teacher_id', auth('teacher')->id()))
            )
            ->with(['session', 'enrollment.student']);

        $columns = collect([
            ['name' => 'session.starts_at', 'label' => __('course_dashboard.session_date') ?? 'Session Date'],
            ['name' => 'enrollment.student.name', 'label' => __('course_dashboard.student_name') ?? 'Student'],
            ['name' => 'status', 'label' => __('course_dashboard.status') ?? 'Status'],
            ['name' => 'note', 'label' => __('course_dashboard.notes') ?? 'Notes'],
            ['name' => 'marked_at', 'label' => __('course_dashboard.marked_at') ?? 'Marked At'],
        ]);

        $service = app(TableExportService::class);
        $filename = 'course_' . $this->record->code . '_attendance_' . now()->format('Y-m-d');

        return $service->exportXlsxFromCached($query->get(), $columns, $filename);
    }

    protected function getOverviewStats(): array
    {
        $course = $this->record;
        $teacherId = auth('teacher')->id();

        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->get();

        $totalEnrolled = $enrollments->count();
        
        $totalPaid = $enrollments->sum(function ($enrollment) {
            return $enrollment->payments()->where('status', PaymentStatus::COMPLETED->value)->sum('amount');
        });

        $totalDue = $enrollments->sum(function ($enrollment) {
            $paid = $enrollment->payments()->where('status', PaymentStatus::COMPLETED->value)->sum('amount');
            return max(0, $enrollment->total_amount - $paid);
        });

        $completedEnrollments = $enrollments->where('status', EnrollmentStatus::COMPLETED)->count();
        $completionRate = $totalEnrolled > 0 ? ($completedEnrollments / $totalEnrolled) * 100 : 0;

        $tasksCount = Task::query()
            ->where('course_id', $course->id)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->count();

        $pendingSubmissions = TaskSubmission::query()
            ->whereHas('task', fn (Builder $q) => 
                $q->where('course_id', $course->id)
                  ->whereHas('course', fn (Builder $q2) => $q2->where('owner_teacher_id', $teacherId))
            )
            ->where('status', 'pending')
            ->count();

        $sessionsCount = CourseSession::query()
            ->where('course_id', $course->id)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->count();

        $totalAttendanceRecords = CourseSessionAttendance::query()
            ->whereHas('session', fn (Builder $q) => 
                $q->where('course_id', $course->id)
                  ->whereHas('course', fn (Builder $q2) => $q2->where('owner_teacher_id', $teacherId))
            )
            ->count();

        $presentCount = CourseSessionAttendance::query()
            ->whereHas('session', fn (Builder $q) => 
                $q->where('course_id', $course->id)
                  ->whereHas('course', fn (Builder $q2) => $q2->where('owner_teacher_id', $teacherId))
            )
            ->where('status', 'present')
            ->count();

        $attendanceRate = $totalAttendanceRecords > 0 ? ($presentCount / $totalAttendanceRecords) * 100 : 0;

        return [
            'total_enrolled' => $totalEnrolled,
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
            'completion_rate' => round($completionRate, 1),
            'tasks_count' => $tasksCount,
            'pending_submissions' => $pendingSubmissions,
            'sessions_count' => $sessionsCount,
            'attendance_rate' => round($attendanceRate, 1),
        ];
    }
}
