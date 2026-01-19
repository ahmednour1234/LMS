<?php

namespace App\Filament\Teacher\Pages\Courses;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamAttempt;
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
use Filament\Actions\Action as PageAction;
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

    protected static ?string $slug = 'courses/{record}/dashboard';

    public ?Course $record = null;

    public ?string $activeTab = 'overview';

    protected ?string $heading = null;

    protected ?string $subheading = null;

    public function mount(Course $record): void
    {
        abort_unless($record->owner_teacher_id === auth('teacher')->id(), 404);

        $this->record = $record;
        $this->heading = MultilingualHelper::formatMultilingualField($record->name);
        $this->subheading = __('course_dashboard.subtitle', ['code' => $record->code]) ?? 'Course Dashboard';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('back')
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

    public function table(Table $table): Table
    {
        return $table->query(Enrollment::query()->where('id', -1));
    }

    public function getRegistrationsTableProperty(): Table
    {
        return $this->registrationsTable($this->makeTable());
    }

    public function getTasksTableProperty(): Table
    {
        return $this->tasksTable($this->makeTable());
    }

    public function getAttendanceTableProperty(): Table
    {
        return $this->attendanceTable($this->makeTable());
    }

    public function getExamsTableProperty(): Table
    {
        return $this->examsTable($this->makeTable());
    }

    public function getExamAttemptsTableProperty(): Table
    {
        return $this->examAttemptsTable($this->makeTable());
    }

    public function getLessonsTableProperty(): Table
    {
        return $this->lessonsTable($this->makeTable());
    }

    public function registrationsTable(Table $table): Table
    {
        return $table
            ->query(
                Enrollment::query()
                    ->where('course_id', $this->record->id)
                    ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                    ->withSum([
                        'payments as paid_sum' => fn ($q) => $q->where('status', PaymentStatus::COMPLETED->value)
                    ], 'amount')
                    ->with(['student', 'course', 'arInvoice'])
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

                TextColumn::make('paid_sum')
                    ->money('OMR')
                    ->label(__('course_dashboard.paid_amount')),

                TextColumn::make('due_amount')
                    ->money('OMR')
                    ->state(function ($record) {
                        $paid = $record->paid_sum ?? 0;
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
                    ->whereHas('task.lesson.section.course', fn (Builder $q) =>
                        $q->where('id', $this->record->id)
                          ->where('owner_teacher_id', auth('teacher')->id())
                    )
                    ->with(['task.lesson.section', 'student', 'mediaFile'])
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
                            ->whereHas('lesson.section.course', fn (Builder $q) =>
                                $q->where('id', $this->record->id)
                                  ->where('owner_teacher_id', auth('teacher')->id())
                            )
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
                            ->whereHas('task.lesson.section.course', fn (Builder $q) =>
                                $q->where('id', $this->record->id)
                                  ->where('owner_teacher_id', auth('teacher')->id())
                            )
                            ->with(['task.lesson.section', 'student']);

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
                            ->whereHas('task.lesson.section.course', fn (Builder $q) =>
                                $q->where('id', $this->record->id)
                                  ->where('owner_teacher_id', auth('teacher')->id())
                            )
                            ->with(['task.lesson.section', 'student']);

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

    public function examsTable(Table $table): Table
    {
        return $table
            ->query(
                Exam::query()
                    ->where(function (Builder $q) {
                        $q->where('course_id', $this->record->id)
                          ->orWhereHas('lesson.section.course', fn (Builder $c) =>
                              $c->where('id', $this->record->id)
                          );
                    })
                    ->whereHas('course', fn (Builder $q) =>
                        $q->where('owner_teacher_id', auth('teacher')->id())
                    )
                    ->withCount('questions')
                    ->with(['lesson.section'])
            )
            ->columns([
                TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.exam_title') ?? 'Title'),

                TextColumn::make('lesson.title')
                    ->formatStateUsing(fn ($state) => $state ? (is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state) : '-')
                    ->label(__('course_dashboard.lesson') ?? 'Lesson'),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match($state) {
                        'mcq' => 'info',
                        'essay' => 'warning',
                        'mixed' => 'success',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.exam_type') ?? 'Type'),

                TextColumn::make('questions_count')
                    ->label(__('course_dashboard.questions_count') ?? 'Questions'),

                TextColumn::make('total_score')
                    ->numeric(2)
                    ->label(__('course_dashboard.total_score') ?? 'Total Score'),

                TextColumn::make('duration_minutes')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ' . __('course_dashboard.minutes') ?? 'min' : '-')
                    ->label(__('course_dashboard.duration') ?? 'Duration'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_dashboard.created_at') ?? 'Created At'),

                TextColumn::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('course_dashboard.active') ?? 'Active' : __('course_dashboard.inactive') ?? 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->label(__('course_dashboard.status') ?? 'Status'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('course_dashboard.exam_type') ?? 'Type')
                    ->options([
                        'mcq' => 'MCQ',
                        'essay' => 'Essay',
                        'mixed' => 'Mixed',
                    ]),

                SelectFilter::make('lesson_id')
                    ->label(__('course_dashboard.filter_by_lesson') ?? 'Filter by Lesson')
                    ->options(function () {
                        return \App\Domain\Training\Models\Lesson::query()
                            ->whereHas('section.course', fn (Builder $q) =>
                                $q->where('id', $this->record->id)
                                  ->where('owner_teacher_id', auth('teacher')->id())
                            )
                            ->get()
                            ->mapWithKeys(function ($lesson) {
                                $title = is_array($lesson->title) ? MultilingualHelper::formatMultilingualField($lesson->title) : $lesson->title;
                                return [$lesson->id => $title];
                            });
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
            ])
            ->actions([
                Action::make('exam_center')
                    ->label(__('course_dashboard.exam_center') ?? 'Exam Center')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->url(fn () => \App\Filament\Teacher\Pages\Courses\CourseExamCenterPage::getUrl(['record' => $this->record])),

                Action::make('view')
                    ->label(__('course_dashboard.view') ?? 'View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \App\Filament\Teacher\Resources\Training\ExamResource::getUrl('view', ['record' => $record->id])),

                Action::make('edit')
                    ->label(__('course_dashboard.edit') ?? 'Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => \App\Filament\Teacher\Resources\Training\ExamResource::getUrl('edit', ['record' => $record->id])),

                Action::make('manage_questions')
                    ->label(__('course_dashboard.manage_questions') ?? 'Manage Questions')
                    ->icon('heroicon-o-question-mark-circle')
                    ->url(fn ($record) => \App\Filament\Teacher\Resources\Training\ExamQuestionResource::getUrl('index', ['exam' => $record->id])),
            ])
            ->headerActions([
                Action::make('exam_center')
                    ->label(__('course_dashboard.exam_center') ?? 'Exam Center')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->url(fn () => \App\Filament\Teacher\Pages\Courses\CourseExamCenterPage::getUrl(['record' => $this->record])),
                Action::make('exportExcel')
                    ->label(__('course_dashboard.export_excel') ?? 'Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $query = Exam::query()
                            ->where(function (Builder $q) {
                                $q->where('course_id', $this->record->id)
                                  ->orWhereHas('lesson.section.course', fn (Builder $c) =>
                                      $c->where('id', $this->record->id)
                                  );
                            })
                            ->whereHas('course', fn (Builder $q) =>
                                $q->where('owner_teacher_id', auth('teacher')->id())
                            )
                            ->withCount('questions')
                            ->with(['lesson.section']);

                        $columns = collect([
                            ['name' => 'title', 'label' => __('course_dashboard.exam_title') ?? 'Title'],
                            ['name' => 'type', 'label' => __('course_dashboard.exam_type') ?? 'Type'],
                            ['name' => 'questions_count', 'label' => __('course_dashboard.questions_count') ?? 'Questions'],
                            ['name' => 'total_score', 'label' => __('course_dashboard.total_score') ?? 'Total Score'],
                            ['name' => 'created_at', 'label' => __('course_dashboard.created_at') ?? 'Created At'],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_exams_' . now()->format('Y-m-d');

                        return $service->exportXlsxFromCached($query->get(), $columns, $filename);
                    }),

                Action::make('exportPdf')
                    ->label(__('course_dashboard.export_pdf') ?? 'Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $query = Exam::query()
                            ->where(function (Builder $q) {
                                $q->where('course_id', $this->record->id)
                                  ->orWhereHas('lesson.section.course', fn (Builder $c) =>
                                      $c->where('id', $this->record->id)
                                  );
                            })
                            ->whereHas('course', fn (Builder $q) =>
                                $q->where('owner_teacher_id', auth('teacher')->id())
                            )
                            ->withCount('questions')
                            ->with(['lesson.section']);

                        $columns = collect([
                            ['name' => 'title', 'label' => __('course_dashboard.exam_title') ?? 'Title'],
                            ['name' => 'type', 'label' => __('course_dashboard.exam_type') ?? 'Type'],
                            ['name' => 'questions_count', 'label' => __('course_dashboard.questions_count') ?? 'Questions'],
                            ['name' => 'total_score', 'label' => __('course_dashboard.total_score') ?? 'Total Score'],
                            ['name' => 'created_at', 'label' => __('course_dashboard.created_at') ?? 'Created At'],
                        ]);

                        $service = app(TableExportService::class);
                        $filename = 'course_' . $this->record->code . '_exams_' . now()->format('Y-m-d');

                        return $service->exportPdfFromCached($query->get(), $columns, $filename, __('course_dashboard.exams_report') ?? 'Exams Report');
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function examAttemptsTable(Table $table): Table
    {
        $teacherId = auth('teacher')->id();
        $courseId = $this->record->id;

        return $table
            ->query(
                ExamAttempt::query()
                    ->whereHas('exam', fn (Builder $q) => 
                        $q->where(function (Builder $q2) use ($courseId) {
                            $q2->where('course_id', $courseId)
                               ->orWhereHas('lesson.section.course', fn (Builder $c) => 
                                   $c->where('id', $courseId)
                               );
                        })
                        ->whereHas('course', fn (Builder $c) => 
                            $c->where('owner_teacher_id', $teacherId)
                        )
                    )
                    ->with(['student', 'enrollment', 'exam.questions'])
                    ->withCount('answers')
            )
            ->columns([
                TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('exam_center.student_name') ?? 'Student Name'),

                TextColumn::make('enrollment.reference')
                    ->searchable()
                    ->label(__('exam_center.enrollment_ref') ?? 'Enrollment Ref'),

                TextColumn::make('exam.title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->label(__('exams.title') ?? 'Exam'),

                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.started_at') ?? 'Started At'),

                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.submitted_at') ?? 'Submitted At'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('attempts.status.' . $state) ?? $state)
                    ->color(fn ($state) => match($state) {
                        'in_progress' => 'warning',
                        'submitted' => 'info',
                        'graded' => 'success',
                        default => 'gray',
                    })
                    ->label(__('attempts.status_label') ?? 'Status'),

                TextColumn::make('score')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->max_score > 0 
                            ? number_format($state ?? 0, 2) . ' / ' . number_format($record->max_score, 2)
                            : '-'
                    )
                    ->label(__('exam_center.score') ?? 'Score'),

                TextColumn::make('percentage')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '-')
                    ->label(__('exam_center.percentage') ?? 'Percentage'),
            ])
            ->filters([
                SelectFilter::make('exam_id')
                    ->label(__('exams.title') ?? 'Exam')
                    ->options(function () use ($courseId, $teacherId) {
                        return Exam::query()
                            ->where(function (Builder $q) use ($courseId) {
                                $q->where('course_id', $courseId)
                                  ->orWhereHas('lesson.section.course', fn (Builder $c) => 
                                      $c->where('id', $courseId)
                                  );
                            })
                            ->whereHas('course', fn (Builder $q) => 
                                $q->where('owner_teacher_id', $teacherId)
                            )
                            ->get()
                            ->mapWithKeys(function ($exam) {
                                $title = is_array($exam->title) ? MultilingualHelper::formatMultilingualField($exam->title) : $exam->title;
                                return [$exam->id => $title];
                            });
                    })
                    ->query(function (Builder $query, $state): Builder {
                        if (!$state || !isset($state['value']) || !$state['value']) {
                            return $query;
                        }
                        return $query->where('exam_id', $state['value']);
                    }),

                SelectFilter::make('status')
                    ->options([
                        'in_progress' => __('attempts.status.in_progress') ?? 'In Progress',
                        'submitted' => __('attempts.status.submitted') ?? 'Submitted',
                        'graded' => __('attempts.status.graded') ?? 'Graded',
                    ])
                    ->label(__('attempts.status_label') ?? 'Status'),

                Filter::make('started_at')
                    ->form([
                        DatePicker::make('started_from')
                            ->label(__('filters.date_from')),
                        DatePicker::make('started_until')
                            ->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['started_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '>=', $date),
                            )
                            ->when(
                                $data['started_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('grade')
                    ->label(__('grading.grade') ?? 'Grade')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'graded')
                    ->url(fn ($record) => \App\Filament\Teacher\Pages\Courses\CourseExamCenterPage::getUrl(['record' => $this->record]) . '?attempt=' . $record->id),

                Action::make('view')
                    ->label(__('exam_center.view') ?? 'View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => __('exam_center.attempt_details') ?? 'Attempt Details')
                    ->modalContent(fn ($record) => view('filament.teacher.modals.exam-attempt-details', ['attempt' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('exam_center.close') ?? 'Close'),

                Action::make('export_pdf')
                    ->label(__('exam_center.export_pdf') ?? 'Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (ExamAttempt $record) {
                        $pdfService = app(PdfService::class);
                        return $pdfService->render('pdf.exam-attempt', [
                            'attempt' => $record->load(['student', 'enrollment', 'exam', 'answers.question']),
                        ]);
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public function lessonsTable(Table $table): Table
    {
        return $table
            ->query(
                \App\Domain\Training\Models\CourseSection::query()
                    ->where('course_id', $this->record->id)
                    ->whereHas('course', fn (Builder $q) =>
                        $q->where('owner_teacher_id', auth('teacher')->id())
                    )
                    ->withCount('lessons')
            )
            ->columns([
                TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.section_title') ?? 'Section'),

                TextColumn::make('lessons_count')
                    ->counts('lessons')
                    ->label(__('course_dashboard.lessons_count') ?? 'Lessons'),

                TextColumn::make('order')
                    ->sortable()
                    ->label(__('course_dashboard.order') ?? 'Order'),

                TextColumn::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('course_dashboard.active') ?? 'Active' : __('course_dashboard.inactive') ?? 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->label(__('course_dashboard.status') ?? 'Status'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_dashboard.created_at') ?? 'Created At'),
            ])
            ->actions([
                Action::make('manage_lessons')
                    ->label(__('course_dashboard.manage_lessons') ?? 'Manage Lessons')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn ($record) => \App\Filament\Teacher\Resources\Training\LessonResource::getUrl('index', ['section' => $record->id])),

                Action::make('create_lesson')
                    ->label(__('course_dashboard.create_lesson') ?? 'Create Lesson')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->url(fn ($record) => \App\Filament\Teacher\Resources\Training\LessonResource::getUrl('create', ['section' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc');
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
            ->whereHas('task.lesson.section.course', fn (Builder $q) =>
                $q->where('id', $course->id)
                  ->where('owner_teacher_id', auth('teacher')->id())
            )
            ->with(['task.lesson.section', 'student'])
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
            ->whereHas('task.lesson.section.course', fn (Builder $q) =>
                $q->where('id', $this->record->id)
                  ->where('owner_teacher_id', auth('teacher')->id())
            )
            ->with(['task.lesson.section', 'student']);

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
            ->withSum([
                'payments as paid_sum' => fn ($q) => $q->where('status', PaymentStatus::COMPLETED->value)
            ], 'amount')
            ->get();

        $totalEnrolled = $enrollments->count();

        $totalPaid = $enrollments->sum('paid_sum') ?? 0;

        $totalDue = $enrollments->map(function ($enrollment) {
            $paid = $enrollment->paid_sum ?? 0;
            return max(0, $enrollment->total_amount - $paid);
        })->sum();

        $completedEnrollments = $enrollments->where('status', EnrollmentStatus::COMPLETED)->count();
        $completionRate = $totalEnrolled > 0 ? ($completedEnrollments / $totalEnrolled) * 100 : 0;

        $tasksCount = Task::query()
            ->whereHas('lesson.section.course', fn (Builder $q) =>
                $q->where('id', $course->id)
                  ->where('owner_teacher_id', $teacherId)
            )
            ->count();

        $examsCount = Exam::query()
            ->whereHas('lesson.section.course', fn (Builder $q) =>
                $q->where('id', $course->id)
                  ->where('owner_teacher_id', $teacherId)
            )
            ->count();

        $pendingSubmissions = TaskSubmission::query()
            ->whereHas('task.lesson.section.course', fn (Builder $q) =>
                $q->where('id', $course->id)
                  ->where('owner_teacher_id', $teacherId)
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
            'exams_count' => $examsCount,
            'pending_submissions' => $pendingSubmissions,
            'sessions_count' => $sessionsCount,
            'attendance_rate' => round($attendanceRate, 1),
        ];
    }
}
