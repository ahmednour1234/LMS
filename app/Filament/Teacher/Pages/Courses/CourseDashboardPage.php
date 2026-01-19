<?php

namespace App\Filament\Teacher\Pages\Courses;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSession;
use App\Domain\Training\Models\CourseSessionAttendance;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamAttempt;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Concerns\HasTableExports;
use App\Services\PdfService;
use App\Services\TableExportService;
use App\Support\Helpers\MultilingualHelper;
use Filament\Actions\Action as PageAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseDashboardPage extends Page implements HasTable
{
    use InteractsWithTable, HasTableExports;

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

    public function getTitle(): string
    {
        return $this->heading ?? __('course_dashboard.title') ?? 'Course Dashboard';
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

    /**
     * Dummy table: الصفحة فيها Multi Tables
     */
    public function table(Table $table): Table
    {
        return $table->query(Enrollment::query()->whereRaw('1=0'));
    }

    // ========= Tables Getters =========

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

    // ========= Helpers =========

    protected function teacherId(): int
    {
        return (int) auth('teacher')->id();
    }

    protected function courseId(): int
    {
        return (int) $this->record->id;
    }

    protected function courseOwnedQuery(Builder $query): Builder
    {
        return $query->where('owner_teacher_id', $this->teacherId());
    }

    // ========= Registrations =========

    public function registrationsTable(Table $table): Table
    {
        return $table
            ->query(
                Enrollment::query()
                    ->where('course_id', $this->courseId())
                    ->whereHas('course', fn (Builder $q) => $this->courseOwnedQuery($q))
                    ->withSum([
                        'payments as paid_sum' => fn ($q) => $q->where('status', PaymentStatus::COMPLETED->value),
                    ], 'amount')
                    ->with(['student', 'course', 'arInvoice'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.reference')),

                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.student_name')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->value ? (__('enrollments.status_options.' . $state->value) ?? $state->value) : (string) $state)
                    ->color(fn ($state) => match ($state) {
                        EnrollmentStatus::ACTIVE => 'success',
                        EnrollmentStatus::PENDING_PAYMENT => 'warning',
                        EnrollmentStatus::COMPLETED => 'info',
                        EnrollmentStatus::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.status')),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('course_dashboard.total_amount')),

                Tables\Columns\TextColumn::make('paid_sum')
                    ->money('OMR')
                    ->label(__('course_dashboard.paid_amount')),

                Tables\Columns\TextColumn::make('due_amount')
                    ->money('OMR')
                    ->state(function ($record) {
                        $paid = (float) ($record->paid_sum ?? 0);
                        $total = (float) ($record->total_amount ?? 0);
                        return max(0, $total - $paid);
                    })
                    ->label(__('course_dashboard.due_amount')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_dashboard.created_at')),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('filters.date_from')),
                        DatePicker::make('created_until')->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),

                SelectFilter::make('payment_status')
                    ->label(__('course_dashboard.payment_status'))
                    ->options([
                        'completed' => __('course_dashboard.payment_status_completed'),
                        'partial' => __('course_dashboard.payment_status_partial'),
                        'pending' => __('course_dashboard.payment_status_pending'),
                    ])
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (!$value) return $query;

                        $completed = PaymentStatus::COMPLETED->value;

                        return $query->where(function (Builder $q) use ($value, $completed) {
                            if ($value === 'completed') {
                                $q->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                ) >= enrollments.total_amount', [$completed]);
                            }

                            if ($value === 'partial') {
                                $q->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                ) > 0', [$completed])
                                  ->whereRaw('(
                                    SELECT COALESCE(SUM(amount), 0)
                                    FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                  ) < enrollments.total_amount', [$completed]);
                            }

                            if ($value === 'pending') {
                                $q->whereRaw('NOT EXISTS (
                                    SELECT 1 FROM payments
                                    WHERE payments.enrollment_id = enrollments.id
                                    AND payments.status = ?
                                )', [$completed]);
                            }
                        });
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('course_dashboard.view'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn () => __('course_dashboard.enrollment_details'))
                    ->modalContent(fn ($record) => view('filament.teacher.modals.enrollment-details', ['enrollment' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('course_dashboard.close')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ========= Tasks =========

    public function tasksTable(Table $table): Table
    {
        return $table
            ->query(
                TaskSubmission::query()
                    ->whereHas('task.lesson.section.course', fn (Builder $q) =>
                        $q->where('id', $this->courseId())
                          ->where('owner_teacher_id', $this->teacherId())
                    )
                    ->with(['task.lesson.section', 'student', 'mediaFile', 'task'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.task_title')),

                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.student_name')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('course_dashboard.submitted_at')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('course_dashboard.submission_status.' . $state) ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'success',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.status')),

                Tables\Columns\TextColumn::make('score')
                    ->formatStateUsing(fn ($state, $record) => $state !== null ? number_format((float)$state, 2) . ' / ' . ((float)($record->task->max_score ?? 0)) : '-')
                    ->label(__('course_dashboard.score')),
            ])
            ->filters([
                SelectFilter::make('task_id')
                    ->label(__('course_dashboard.filter_by_task'))
                    ->options(function () {
                        return Task::query()
                            ->whereHas('lesson.section.course', fn (Builder $q) =>
                                $q->where('id', $this->courseId())
                                  ->where('owner_teacher_id', $this->teacherId())
                            )
                            ->get()
                            ->mapWithKeys(function ($task) {
                                $title = is_array($task->title) ? MultilingualHelper::formatMultilingualField($task->title) : $task->title;
                                return [$task->id => $title];
                            })
                            ->all();
                    })
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        return $value ? $query->where('task_id', $value) : $query;
                    }),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('filters.date_from')),
                        DatePicker::make('created_until')->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
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
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn ($record) => (float) ($record->task->max_score ?? 100))
                            ->label(__('course_dashboard.score')),

                        Forms\Components\Textarea::make('feedback')
                            ->rows(3)
                            ->label(__('course_dashboard.feedback')),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'score' => $data['score'],
                            'feedback' => $data['feedback'] ?? null,
                            'reviewed_at' => now(),
                            'reviewed_by' => $this->teacherId(),
                            'status' => 'reviewed',
                        ]);

                        Notification::make()
                            ->title(__('course_dashboard.graded_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ========= Exams =========

    public function examsTable(Table $table): Table
    {
        $courseId = $this->courseId();
        $teacherId = $this->teacherId();

        return $table
            ->query(
                Exam::query()
                    ->where(function (Builder $q) use ($courseId) {
                        $q->where('course_id', $courseId)
                          ->orWhereHas('lesson.section', fn (Builder $s) => $s->where('course_id', $courseId));
                    })
                    ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
                    ->withCount('questions')
                    ->with(['lesson.section'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.exam_title') ?? 'Title'),

                Tables\Columns\TextColumn::make('lesson.title')
                    ->formatStateUsing(fn ($state) => $state ? (is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state) : '-')
                    ->label(__('course_dashboard.lesson') ?? 'Lesson'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string)$state))
                    ->color(fn ($state) => match ((string)$state) {
                        'mcq' => 'info',
                        'essay' => 'warning',
                        'mixed' => 'success',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.exam_type') ?? 'Type'),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label(__('course_dashboard.questions_count') ?? 'Questions'),

                Tables\Columns\TextColumn::make('total_score')
                    ->numeric(2)
                    ->label(__('course_dashboard.total_score') ?? 'Total Score'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ' . ((__('course_dashboard.minutes') ?? 'min')) : '-')
                    ->label(__('course_dashboard.duration') ?? 'Duration'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_dashboard.created_at') ?? 'Created At'),

                Tables\Columns\TextColumn::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? (__('course_dashboard.active') ?? 'Active') : (__('course_dashboard.inactive') ?? 'Inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->label(__('course_dashboard.status') ?? 'Status'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('course_dashboard.exam_type') ?? 'Type')
                    ->options(['mcq' => 'MCQ', 'essay' => 'Essay', 'mixed' => 'Mixed']),

                SelectFilter::make('lesson_id')
                    ->label(__('course_dashboard.filter_by_lesson') ?? 'Filter by Lesson')
                    ->options(function () use ($courseId, $teacherId) {
                        return Lesson::query()
                            ->whereHas('section', fn (Builder $s) => $s->where('course_id', $courseId))
                            ->whereHas('section.course', fn (Builder $c) => $c->where('owner_teacher_id', $teacherId))
                            ->get()
                            ->mapWithKeys(function ($lesson) {
                                $title = is_array($lesson->title) ? MultilingualHelper::formatMultilingualField($lesson->title) : $lesson->title;
                                return [$lesson->id => $title];
                            })
                            ->all();
                    }),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('filters.date_from')),
                        DatePicker::make('created_until')->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
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
            ->defaultSort('created_at', 'desc');
    }

    // ========= Exam Attempts =========

    public function examAttemptsTable(Table $table): Table
    {
        $teacherId = $this->teacherId();
        $courseId = $this->courseId();

        return $table
            ->query(
                ExamAttempt::query()
                    ->whereHas('exam', function (Builder $q) use ($courseId, $teacherId) {
                        $q->where(function (Builder $q2) use ($courseId) {
                            $q2->where('course_id', $courseId)
                               ->orWhereHas('lesson.section', fn (Builder $s) => $s->where('course_id', $courseId));
                        })
                        ->whereHas('course', fn (Builder $c) => $c->where('owner_teacher_id', $teacherId));
                    })
                    ->with(['student', 'enrollment', 'exam.questions'])
                    ->withCount('answers')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('exam_center.student_name') ?? 'Student Name'),

                Tables\Columns\TextColumn::make('enrollment.reference')
                    ->searchable()
                    ->label(__('exam_center.enrollment_ref') ?? 'Enrollment Ref'),

                Tables\Columns\TextColumn::make('exam.title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->label(__('exams.title') ?? 'Exam'),

                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.started_at') ?? 'Started At'),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.submitted_at') ?? 'Submitted At'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('attempts.status.' . $state) ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'in_progress' => 'warning',
                        'submitted' => 'info',
                        'graded' => 'success',
                        default => 'gray',
                    })
                    ->label(__('attempts.status_label') ?? 'Status'),

                Tables\Columns\TextColumn::make('score')
                    ->formatStateUsing(fn ($state, $record) =>
                        (float)($record->max_score ?? 0) > 0
                            ? number_format((float)($state ?? 0), 2) . ' / ' . number_format((float)$record->max_score, 2)
                            : '-'
                    )
                    ->label(__('exam_center.score') ?? 'Score'),

                Tables\Columns\TextColumn::make('percentage')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float)$state, 1) . '%' : '-')
                    ->label(__('exam_center.percentage') ?? 'Percentage'),
            ])
            ->filters([
                SelectFilter::make('exam_id')
                    ->label(__('exams.title') ?? 'Exam')
                    ->options(function () use ($courseId, $teacherId) {
                        return Exam::query()
                            ->where(function (Builder $q) use ($courseId) {
                                $q->where('course_id', $courseId)
                                  ->orWhereHas('lesson.section', fn (Builder $s) => $s->where('course_id', $courseId));
                            })
                            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
                            ->get()
                            ->mapWithKeys(function ($exam) {
                                $title = is_array($exam->title) ? MultilingualHelper::formatMultilingualField($exam->title) : $exam->title;
                                return [$exam->id => $title];
                            })
                            ->all();
                    })
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        return $value ? $query->where('exam_id', $value) : $query;
                    }),

                SelectFilter::make('status')
                    ->label(__('attempts.status_label') ?? 'Status')
                    ->options([
                        'in_progress' => __('attempts.status.in_progress') ?? 'In Progress',
                        'submitted' => __('attempts.status.submitted') ?? 'Submitted',
                        'graded' => __('attempts.status.graded') ?? 'Graded',
                    ]),

                Filter::make('started_at')
                    ->form([
                        DatePicker::make('started_from')->label(__('filters.date_from')),
                        DatePicker::make('started_until')->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['started_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('started_at', '>=', $date))
                            ->when($data['started_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('started_at', '<=', $date));
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
                    ->modalHeading(fn () => __('exam_center.attempt_details') ?? 'Attempt Details')
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

    // ========= Attendance =========

    public function attendanceTable(Table $table): Table
    {
        return $table
            ->query(
                CourseSessionAttendance::query()
                    ->whereHas('session', fn (Builder $q) =>
                        $q->where('course_id', $this->courseId())
                          ->whereHas('course', fn (Builder $c) => $c->where('owner_teacher_id', $this->teacherId()))
                    )
                    ->with(['session', 'enrollment.student'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('session.starts_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_dashboard.session_date')),

                Tables\Columns\TextColumn::make('enrollment.student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_dashboard.student_name')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('course_dashboard.attendance_status.' . (is_object($state) && property_exists($state, 'value') ? $state->value : $state)) ?? (is_object($state) && property_exists($state, 'value') ? $state->value : $state))
                    ->color(fn ($state) => match (is_object($state) && property_exists($state, 'value') ? $state->value : $state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'excused' => 'info',
                        default => 'gray',
                    })
                    ->label(__('course_dashboard.status')),

                Tables\Columns\TextColumn::make('note')
                    ->limit(50)
                    ->label(__('course_dashboard.notes')),

                Tables\Columns\TextColumn::make('marked_at')
                    ->dateTime()
                    ->label(__('course_dashboard.marked_at')),
            ])
            ->filters([
                SelectFilter::make('session_id')
                    ->label(__('course_dashboard.filter_by_session'))
                    ->options(function () {
                        return CourseSession::query()
                            ->where('course_id', $this->courseId())
                            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $this->teacherId()))
                            ->orderBy('starts_at', 'desc')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => $s->starts_at?->format('Y-m-d H:i') ?? ('Session #' . $s->id)])
                            ->all();
                    })
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        return $value ? $query->where('session_id', $value) : $query;
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
                        $value = $state['value'] ?? null;
                        return $value ? $query->where('status', $value) : $query;
                    }),

                Filter::make('marked_at')
                    ->form([
                        DatePicker::make('marked_from')->label(__('filters.date_from')),
                        DatePicker::make('marked_until')->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['marked_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('marked_at', '>=', $date))
                            ->when($data['marked_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('marked_at', '<=', $date));
                    }),
            ])
            ->defaultSort('session.starts_at', 'desc');
    }

    // ========= LESSONS (المطلوب) =========
    // بدل CourseSection::query() -> هنجيب من جدول lessons مباشرة
    // lessons.section_id -> course_sections.id -> course_sections.course_id -> courses.id
    // courses.owner_teacher_id

    public function lessonsTable(Table $table): Table
    {
        $courseId = $this->courseId();
        $teacherId = $this->teacherId();

        return $table
            ->query(
                Lesson::query()
                    ->whereHas('section', fn (Builder $s) => $s->where('course_id', $courseId))
                    ->whereHas('section.course', fn (Builder $c) => $c->where('owner_teacher_id', $teacherId))
                    ->with(['section'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('section.title')
                    ->label(__('course_dashboard.section_title') ?? 'Section')
                    ->formatStateUsing(fn ($state) => $state ? (is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state) : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('course_dashboard.lesson') ?? 'Lesson')
                    ->formatStateUsing(fn ($state) => is_array($state) ? MultilingualHelper::formatMultilingualField($state) : $state)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->label(__('course_dashboard.order') ?? 'Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('is_active')
                    ->badge()
                    ->label(__('course_dashboard.status') ?? 'Status')
                    ->formatStateUsing(fn ($state) => $state ? (__('course_dashboard.active') ?? 'Active') : (__('course_dashboard.inactive') ?? 'Inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'danger'),

                Tables\Columns\ aTextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('course_dashboard.created_at') ?? 'Created At')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('section_id')
                    ->label(__('course_dashboard.filter_by_section') ?? 'Filter by Section')
                    ->options(function () use ($courseId, $teacherId) {
                        return \App\Domain\Training\Models\CourseSection::query()
                            ->where('course_id', $courseId)
                            ->whereHas('course', fn (Builder $c) => $c->where('owner_teacher_id', $teacherId))
                            ->orderBy('order')
                            ->get()
                            ->mapWithKeys(function ($section) {
                                $title = is_array($section->title) ? MultilingualHelper::formatMultilingualField($section->title) : $section->title;
                                return [$section->id => $title];
                            })
                            ->all();
                    })
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        return $value ? $query->where('section_id', $value) : $query;
                    }),

                SelectFilter::make('is_active')
                    ->label(__('course_dashboard.status') ?? 'Status')
                    ->options([
                        1 => __('course_dashboard.active') ?? 'Active',
                        0 => __('course_dashboard.inactive') ?? 'Inactive',
                    ])
                    ->query(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        return $value === null ? $query : $query->where('is_active', (int)$value);
                    }),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('filters.date_from')),
                        DatePicker::make('created_until')->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('manage')
                    ->label(__('course_dashboard.manage_lesson') ?? 'Manage')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => \App\Filament\Teacher\Resources\Training\LessonResource::getUrl('edit', ['record' => $record->id])),

                Action::make('view')
                    ->label(__('course_dashboard.view') ?? 'View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \App\Filament\Teacher\Resources\Training\LessonResource::getUrl('view', ['record' => $record->id])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ========= Overview Stats (كما عندك) =========

    protected function getOverviewStats(): array
    {
        $courseId = $this->courseId();
        $teacherId = $this->teacherId();

        $enrollments = Enrollment::query()
            ->where('course_id', $courseId)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', PaymentStatus::COMPLETED->value)], 'amount')
            ->get();

        $totalEnrolled = $enrollments->count();
        $totalPaid = (float) ($enrollments->sum('paid_sum') ?? 0);

        $totalDue = (float) $enrollments->map(function ($enrollment) {
            $paid = (float) ($enrollment->paid_sum ?? 0);
            $total = (float) ($enrollment->total_amount ?? 0);
            return max(0, $total - $paid);
        })->sum();

        $completedEnrollments = $enrollments->where('status', EnrollmentStatus::COMPLETED)->count();
        $completionRate = $totalEnrolled > 0 ? ($completedEnrollments / $totalEnrolled) * 100 : 0;

        $tasksCount = Task::query()
            ->whereHas('lesson.section.course', fn (Builder $q) => $q->where('id', $courseId)->where('owner_teacher_id', $teacherId))
            ->count();

        $examsCount = Exam::query()
            ->whereHas('lesson.section.course', fn (Builder $q) => $q->where('id', $courseId)->where('owner_teacher_id', $teacherId))
            ->count();

        $pendingSubmissions = TaskSubmission::query()
            ->whereHas('task.lesson.section.course', fn (Builder $q) => $q->where('id', $courseId)->where('owner_teacher_id', $teacherId))
            ->where('status', 'pending')
            ->count();

        $sessionsCount = CourseSession::query()
            ->where('course_id', $courseId)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->count();

        $totalAttendanceRecords = CourseSessionAttendance::query()
            ->whereHas('session', fn (Builder $q) =>
                $q->where('course_id', $courseId)
                  ->whereHas('course', fn (Builder $c) => $c->where('owner_teacher_id', $teacherId))
            )
            ->count();

        $presentCount = CourseSessionAttendance::query()
            ->whereHas('session', fn (Builder $q) =>
                $q->where('course_id', $courseId)
                  ->whereHas('course', fn (Builder $c) => $c->where('owner_teacher_id', $teacherId))
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
