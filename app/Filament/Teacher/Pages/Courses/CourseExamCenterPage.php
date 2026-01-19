<?php

namespace App\Filament\Teacher\Pages\Courses;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamAttempt;
use App\Domain\Training\Models\ExamQuestion;
use App\Filament\Teacher\Resources\Training\CourseResource;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action as PageAction;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CourseExamCenterPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static string $view = 'filament.teacher.pages.courses.course-exam-center';

    protected static ?string $slug = 'courses/{record}/exams';

    public ?Course $record = null;

    public ?string $activeTab = 'exams_list';

    protected ?string $heading = null;

    protected ?string $subheading = null;

    public ?Exam $selectedExam = null;

    public ?ExamAttempt $selectedAttempt = null;

    public function mount(Course $record): void
    {
        abort_unless($record->owner_teacher_id === auth('teacher')->id(), 404);

        $this->record = $record;
        $this->heading = __('exam_center.title', ['course' => MultilingualHelper::formatMultilingualField($record->name)]);
        $this->subheading = __('exam_center.subtitle', ['code' => $record->code]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('back')
                ->label(__('exam_center.back_to_dashboard'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => CourseDashboardPage::getUrl(['record' => $this->record])),
        ];
    }

    public function getTitle(): string
    {
        return $this->heading ?? __('exam_center.title');
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab !== 'exam_builder') {
            $this->selectedExam = null;
        }
        if ($this->activeTab !== 'grading') {
            $this->selectedAttempt = null;
        }
    }

    protected function getForms(): array
    {
        return [
            'examBuilderForm',
            'gradingForm',
        ];
    }

    protected function getKpiStats(): array
    {
        $teacherId = auth('teacher')->id();
        $courseId = $this->record->id;

        $totalExams = Exam::query()
            ->where('course_id', $courseId)
            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->count();

        $totalAttempts = ExamAttempt::query()
            ->whereHas('exam', fn (Builder $q) => 
                $q->where('course_id', $courseId)
                  ->whereHas('course', fn ($c) => $c->where('owner_teacher_id', $teacherId))
            )
            ->count();

        $pendingGrading = ExamAttempt::query()
            ->whereHas('exam', fn (Builder $q) => 
                $q->where('course_id', $courseId)
                  ->whereHas('course', fn ($c) => $c->where('owner_teacher_id', $teacherId))
            )
            ->where('status', 'submitted')
            ->whereHas('answers.question', fn ($q) => 
                $q->whereIn('type', ['essay', 'short_answer'])
            )
            ->count();

        $avgScore = ExamAttempt::query()
            ->whereHas('exam', fn (Builder $q) => 
                $q->where('course_id', $courseId)
                  ->whereHas('course', fn ($c) => $c->where('owner_teacher_id', $teacherId))
            )
            ->where('status', 'graded')
            ->avg('percentage') ?? 0;

        return [
            'total_exams' => $totalExams,
            'total_attempts' => $totalAttempts,
            'pending_grading' => $pendingGrading,
            'avg_score' => round($avgScore, 1),
        ];
    }

    public function examsListTable(Table $table): Table
    {
        $teacherId = auth('teacher')->id();
        $courseId = $this->record->id;

        return $table
            ->query(
                Exam::query()
                    ->where('course_id', $courseId)
                    ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
                    ->withCount('questions')
                    ->withCount('attempts')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->searchable()
                    ->sortable()
                    ->label(__('exams.title')),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('exams.type_options.' . $state))
                    ->color(fn ($state) => match($state) {
                        'mcq' => 'info',
                        'essay' => 'warning',
                        'mixed' => 'success',
                        default => 'gray',
                    })
                    ->label(__('exams.type')),

                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label(__('exam_center.questions_count')),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->counts('attempts')
                    ->label(__('exam_center.attempts_count')),

                Tables\Columns\TextColumn::make('total_score')
                    ->numeric(2)
                    ->label(__('exams.total_score')),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('exams.is_active')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.created_at')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'mcq' => __('exams.type_options.mcq'),
                        'essay' => __('exams.type_options.essay'),
                        'mixed' => __('exams.type_options.mixed'),
                    ])
                    ->label(__('exams.type')),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('exams.is_active')),
            ])
            ->actions([
                Action::make('edit')
                    ->label(__('exam_center.edit'))
                    ->icon('heroicon-o-pencil')
                    ->action(function (Exam $record) {
                        $this->selectedExam = $record;
                        $this->activeTab = 'exam_builder';
                    }),

                Action::make('publish')
                    ->label(fn (Exam $record) => $record->is_active ? __('exam_center.unpublish') : __('exam_center.publish'))
                    ->icon(fn (Exam $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Exam $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (Exam $record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? __('exam_center.published') : __('exam_center.unpublished'))
                            ->success()
                            ->send();
                    }),

                Action::make('delete')
                    ->label(__('exam_center.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Exam $record) {
                        $record->delete();
                        Notification::make()
                            ->title(__('exam_center.deleted'))
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('exam_center.create_exam'))
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->action(function () {
                        $this->selectedExam = new Exam();
                        $this->selectedExam->course_id = $this->record->id;
                        $this->activeTab = 'exam_builder';
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function attemptsTable(Table $table): Table
    {
        $teacherId = auth('teacher')->id();
        $courseId = $this->record->id;

        return $table
            ->query(
                ExamAttempt::query()
                    ->whereHas('exam', fn (Builder $q) => 
                        $q->where('course_id', $courseId)
                          ->whereHas('course', fn ($c) => $c->where('owner_teacher_id', $teacherId))
                    )
                    ->with(['student', 'enrollment', 'exam.questions'])
                    ->withCount('answers')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('exam_center.student_name')),

                Tables\Columns\TextColumn::make('enrollment.reference')
                    ->searchable()
                    ->label(__('exam_center.enrollment_ref')),

                Tables\Columns\TextColumn::make('exam.title')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->label(__('exams.title')),

                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.started_at')),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.submitted_at')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('attempts.status.' . $state))
                    ->color(fn ($state) => match($state) {
                        'in_progress' => 'warning',
                        'submitted' => 'info',
                        'graded' => 'success',
                        default => 'gray',
                    })
                    ->label(__('attempts.status_label')),

                Tables\Columns\TextColumn::make('score')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->max_score > 0 
                            ? number_format($state, 2) . ' / ' . number_format($record->max_score, 2)
                            : '-'
                    )
                    ->label(__('exam_center.score')),

                Tables\Columns\TextColumn::make('percentage')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '-')
                    ->label(__('exam_center.percentage')),

                Tables\Columns\TextColumn::make('needs_grading')
                    ->label('')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && $record->status === 'submitted' && $record->exam && $record->exam->questions()->whereIn('type', ['essay', 'short_answer'])->exists()) {
                            return __('exam_center.needs_grading');
                        }
                        return '';
                    })
                    ->badge()
                    ->color('warning')
                    ->visible(fn ($record) => 
                        $record && 
                        $record->status === 'submitted' && 
                        $record->exam && 
                        $record->exam->questions()->whereIn('type', ['essay', 'short_answer'])->exists()
                    ),
            ])
            ->filters([
                SelectFilter::make('exam_id')
                    ->label(__('exams.title'))
                    ->relationship('exam', 'id', fn (Builder $query) => 
                        $query->where('course_id', $courseId)
                              ->whereHas('course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
                    )
                    ->getOptionLabelUsing(function ($value) {
                        $exam = Exam::find($value);
                        return $exam ? MultilingualHelper::formatMultilingualField($exam->title) : '';
                    }),

                SelectFilter::make('status')
                    ->options([
                        'in_progress' => __('attempts.status.in_progress'),
                        'submitted' => __('attempts.status.submitted'),
                        'graded' => __('attempts.status.graded'),
                    ])
                    ->label(__('attempts.status_label')),

                Filter::make('started_at')
                    ->form([
                        Forms\Components\DatePicker::make('started_from')
                            ->label(__('filters.date_from')),
                        Forms\Components\DatePicker::make('started_until')
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
                    ->label(__('grading.grade'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'graded')
                    ->action(function (ExamAttempt $record) {
                        $this->selectedAttempt = $record->load(['answers.question', 'exam.questions']);
                        $this->selectedExam = $this->selectedAttempt->exam;
                        $this->activeTab = 'grading';
                    }),

                Action::make('view')
                    ->label(__('exam_center.view'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => __('exam_center.attempt_details'))
                    ->modalContent(fn ($record) => view('filament.teacher.modals.exam-attempt-details', ['attempt' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('exam_center.close')),

                Action::make('export_pdf')
                    ->label(__('exam_center.export_pdf'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (ExamAttempt $record) {
                        return $this->exportAttemptPdf($record);
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    protected function exportAttemptPdf(ExamAttempt $attempt)
    {
        $pdfService = app(\App\Services\PdfService::class);
        return $pdfService->render('pdf.exam-attempt', [
            'attempt' => $attempt->load(['student', 'enrollment', 'exam', 'answers.question']),
        ]);
    }

    public function exportExamResultsPdf(Exam $exam)
    {
        $teacherId = auth('teacher')->id();
        
        $attempts = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->whereHas('exam.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->with(['student', 'enrollment'])
            ->get();

        $pdfService = app(\App\Services\PdfService::class);
        return $pdfService->render('pdf.exam-results-summary', [
            'exam' => $exam,
            'attempts' => $attempts,
        ]);
    }

    public function examBuilderForm(Forms\Form $form): Forms\Form
    {
        if (!$this->selectedExam) {
            $this->selectedExam = new Exam(['course_id' => $this->record->id]);
        }
        
        return $form
            ->model($this->selectedExam)
            ->schema([
                Forms\Components\Section::make(__('exam_center.exam_details'))
                    ->schema([
                        Forms\Components\TextInput::make('title.ar')
                            ->label(__('exams.title_ar'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('title.en')
                            ->label(__('exams.title_en'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description.ar')
                            ->label(__('exams.description_ar'))
                            ->rows(3)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description.en')
                            ->label(__('exams.description_en'))
                            ->rows(3)
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->options([
                                'mcq' => __('exams.type_options.mcq'),
                                'essay' => __('exams.type_options.essay'),
                                'mixed' => __('exams.type_options.mixed'),
                            ])
                            ->required()
                            ->label(__('exams.type'))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->label(__('exams.duration_minutes'))
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('exams.is_active'))
                            ->default(true)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total_score')
                            ->label(__('exams.total_grade'))
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record && $record->exists) {
                                    $totalPoints = $record->questions()->sum('points');
                                    $component->state($totalPoints);
                                } else {
                                    $component->state(0);
                                }
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('exam_center.questions'))
                    ->schema([
                        Forms\Components\Repeater::make('questions')
                            ->relationship('questions', fn ($record) => $record ? $record->questions()->orderBy('order') : null)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'mcq' => __('exam_questions.type_options.mcq'),
                                        'true_false' => __('exam_questions.type_options.true_false'),
                                        'essay' => __('exam_questions.type_options.essay'),
                                        'short_answer' => __('exam_questions.type_options.short_answer'),
                                    ])
                                    ->required()
                                    ->label(__('exam_questions.type'))
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        if ($state === 'true_false') {
                                            $set('options', ExamQuestion::getTrueFalseOptions());
                                        } elseif ($state !== 'mcq') {
                                            $set('options', []);
                                        }
                                    }),

                                Forms\Components\Textarea::make('question.ar')
                                    ->label(__('exam_questions.question_ar'))
                                    ->required()
                                    ->rows(3),

                                Forms\Components\Textarea::make('question.en')
                                    ->label(__('exam_questions.question_en'))
                                    ->required()
                                    ->rows(3),

                                Forms\Components\TextInput::make('points')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->label(__('exam_questions.points')),

                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->default(0)
                                    ->label(__('exam_questions.order')),

                                Forms\Components\Toggle::make('required')
                                    ->label(__('exam_questions.required'))
                                    ->default(true),

                                Forms\Components\Repeater::make('options')
                                    ->schema([
                                        Forms\Components\TextInput::make('text_ar')
                                            ->label(__('exam_center.option_text_ar'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('text_en')
                                            ->label(__('exam_center.option_text_en'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\Toggle::make('is_correct')
                                            ->label(__('exam_center.is_correct'))
                                            ->default(false),

                                        Forms\Components\TextInput::make('order')
                                            ->numeric()
                                            ->default(0)
                                            ->label(__('exam_questions.order')),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->visible(fn ($get) => in_array($get('type'), ['mcq', 'true_false']))
                                    ->label(__('exam_questions.options')),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['question']) && is_array($state['question'])
                                    ? (MultilingualHelper::formatMultilingualField($state['question']) ?: __('exam_center.question'))
                                    : __('exam_center.question')
                            )
                            ->addActionLabel(__('exam_center.add_question'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->label(__('exam_center.questions')),
                    ]),
            ])
            ->statePath('selectedExam');
    }

    public function saveExam(): void
    {
        $data = $this->examBuilderForm->getState();

        DB::transaction(function () use ($data) {
            if (isset($data['id']) && $data['id']) {
                $exam = Exam::findOrFail($data['id']);
                $exam->update([
                    'course_id' => $this->record->id,
                    'title' => $data['title'] ?? [],
                    'description' => $data['description'] ?? [],
                    'type' => $data['type'],
                    'duration_minutes' => $data['duration_minutes'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);
            } else {
                $exam = Exam::create([
                    'course_id' => $this->record->id,
                    'title' => $data['title'] ?? [],
                    'description' => $data['description'] ?? [],
                    'type' => $data['type'],
                    'duration_minutes' => $data['duration_minutes'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);
            }

            if (isset($data['questions'])) {
                $questionIds = [];
                foreach ($data['questions'] as $index => $questionData) {
                    if (isset($questionData['id']) && $questionData['id']) {
                        $question = ExamQuestion::find($questionData['id']);
                        if ($question && $question->exam_id === $exam->id) {
                            $question->update([
                                'type' => $questionData['type'],
                                'question' => $questionData['question'] ?? [],
                                'options' => $questionData['options'] ?? [],
                                'correct_answer' => $questionData['correct_answer'] ?? null,
                                'points' => $questionData['points'] ?? 0,
                                'order' => $questionData['order'] ?? $index,
                                'required' => $questionData['required'] ?? true,
                                'is_active' => true,
                            ]);
                        } else {
                            $question = ExamQuestion::create([
                                'exam_id' => $exam->id,
                                'type' => $questionData['type'],
                                'question' => $questionData['question'] ?? [],
                                'options' => $questionData['options'] ?? [],
                                'correct_answer' => $questionData['correct_answer'] ?? null,
                                'points' => $questionData['points'] ?? 0,
                                'order' => $questionData['order'] ?? $index,
                                'required' => $questionData['required'] ?? true,
                                'is_active' => true,
                            ]);
                        }
                    } else {
                        $question = ExamQuestion::create([
                            'exam_id' => $exam->id,
                            'type' => $questionData['type'],
                            'question' => $questionData['question'] ?? [],
                            'options' => $questionData['options'] ?? [],
                            'correct_answer' => $questionData['correct_answer'] ?? null,
                            'points' => $questionData['points'] ?? 0,
                            'order' => $questionData['order'] ?? $index,
                            'required' => $questionData['required'] ?? true,
                            'is_active' => true,
                        ]);
                    }
                    $questionIds[] = $question->id;
                }

                ExamQuestion::where('exam_id', $exam->id)
                    ->whereNotIn('id', $questionIds)
                    ->delete();
            }

            $totalScore = $exam->questions()->sum('points');
            $exam->update(['total_score' => $totalScore]);

            $this->selectedExam = $exam;
        });

        Notification::make()
            ->title(__('exam_center.saved'))
            ->success()
            ->send();
    }

    public function gradingForm(Forms\Form $form): Forms\Form
    {
        if (!$this->selectedAttempt) {
            return $form
                ->schema([
                    Forms\Components\Placeholder::make('no_attempt')
                        ->label('')
                        ->content(__('exam_center.select_attempt_to_grade')),
                ]);
        }

        $attempt = $this->selectedAttempt->load(['answers.question', 'exam.questions']);
        $questions = $attempt->exam->questions()->orderBy('order')->get();

        $schema = [];
        foreach ($questions as $question) {
            $answer = $attempt->answers->firstWhere('question_id', $question->id);
            
            $schema[] = Forms\Components\Section::make($question->order . '. ' . MultilingualHelper::formatMultilingualField($question->question))
                ->schema([
                    Forms\Components\Placeholder::make('question_type')
                        ->label(__('exam_questions.type'))
                        ->content(__('exam_questions.type_options.' . $question->type)),

                    Forms\Components\Placeholder::make('points_possible')
                        ->label(__('exam_center.points_possible'))
                        ->content($question->points),

                    Forms\Components\Placeholder::make('student_answer')
                        ->label(__('exam_center.student_answer'))
                        ->content(function () use ($answer, $question) {
                            if (!$answer) {
                                return __('exam_center.no_answer');
                            }
                            
                            if (in_array($question->type, ['mcq', 'true_false'])) {
                                $options = $question->options ?? [];
                                $selectedOption = collect($options)->firstWhere('order', $answer->answer);
                                if ($selectedOption) {
                                    return MultilingualHelper::formatMultilingualField([
                                        'ar' => $selectedOption['text_ar'] ?? '',
                                        'en' => $selectedOption['text_en'] ?? '',
                                    ]);
                                }
                                return $answer->answer ?? __('exam_center.no_answer');
                            }
                            
                            return $answer->answer ?? __('exam_center.no_answer');
                        }),

                    ...(in_array($question->type, ['essay', 'short_answer']) ? [
                        Forms\Components\TextInput::make("answers.{$question->id}.points_earned")
                            ->label(__('grading.points_earned'))
                            ->numeric()
                            ->default(fn () => $answer?->points_earned ?? 0)
                            ->minValue(0)
                            ->maxValue($question->points)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) use ($question) {
                                $maxPoints = $question->points;
                                if ($state > $maxPoints) {
                                    $set("answers.{$question->id}.points_earned", $maxPoints);
                                }
                            }),

                        Forms\Components\Textarea::make("answers.{$question->id}.feedback")
                            ->label(__('grading.feedback'))
                            ->rows(3)
                            ->default(fn () => $answer?->feedback ?? ''),
                    ] : [
                        Forms\Components\Placeholder::make('auto_grade')
                            ->label(__('grading.auto_grade'))
                            ->content(function () use ($answer, $question) {
                                if (!$answer) {
                                    return __('exam_center.not_answered');
                                }
                                
                                $isCorrect = $this->checkAnswerCorrectness($answer, $question);
                                $answer->update(['is_correct' => $isCorrect]);
                                
                                if ($isCorrect) {
                                    $pointsEarned = $question->points;
                                } else {
                                    $pointsEarned = 0;
                                }
                                
                                if ($answer->points_earned !== $pointsEarned) {
                                    $answer->update(['points_earned' => $pointsEarned]);
                                }
                                
                                return $isCorrect 
                                    ? __('grading.correct') . ' (' . $pointsEarned . ' ' . __('exam_center.points') . ')'
                                    : __('grading.incorrect') . ' (0 ' . __('exam_center.points') . ')';
                            }),

                        Forms\Components\TextInput::make("answers.{$question->id}.points_earned_override")
                            ->label(__('grading.override_points'))
                            ->numeric()
                            ->default(fn () => $answer?->points_earned ?? 0)
                            ->minValue(0)
                            ->maxValue($question->points)
                            ->helperText(__('grading.override_helper')),
                    ]),
                ])
                ->collapsible();
        }

        return $form
            ->model($this->selectedAttempt)
            ->schema($schema);
    }

    protected function checkAnswerCorrectness($answer, $question): bool
    {
        if (!in_array($question->type, ['mcq', 'true_false'])) {
            return false;
        }

        $options = $question->options ?? [];
        $selectedOption = collect($options)->firstWhere('order', $answer->answer);
        
        return $selectedOption && ($selectedOption['is_correct'] ?? false);
    }

    public function saveGrading(): void
    {
        if (!$this->selectedAttempt) {
            return;
        }

        $data = $this->gradingForm->getState();
        $attempt = $this->selectedAttempt->load(['answers', 'exam.questions']);

        DB::transaction(function () use ($data, $attempt) {
            $totalScore = 0;
            $maxScore = $attempt->exam->questions()->sum('points');

            foreach ($attempt->exam->questions as $question) {
                $answer = $attempt->answers->firstWhere('question_id', $question->id);
                
                if (!$answer) {
                    continue;
                }

                if (in_array($question->type, ['essay', 'short_answer'])) {
                    $pointsEarned = $data['answers'][$question->id]['points_earned'] ?? 0;
                    $feedback = $data['answers'][$question->id]['feedback'] ?? null;
                    
                    $answer->update([
                        'points_earned' => min($pointsEarned, $question->points),
                        'points_possible' => $question->points,
                        'feedback' => $feedback,
                    ]);
                } else {
                    $overridePoints = $data['answers'][$question->id]['points_earned_override'] ?? null;
                    if ($overridePoints !== null) {
                        $answer->update([
                            'points_earned' => min($overridePoints, $question->points),
                        ]);
                    }
                }

                $totalScore += $answer->points_earned;
            }

            $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

            $attempt->update([
                'score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'status' => 'graded',
                'graded_at' => now(),
            ]);
        });

        Notification::make()
            ->title(__('grading.saved'))
            ->success()
            ->send();

        $this->selectedAttempt->refresh();
    }

    public function getExamsListTableProperty(): Table
    {
        return $this->examsListTable($this->makeTable());
    }

    public function getAttemptsTableProperty(): Table
    {
        return $this->attemptsTable($this->makeTable());
    }

    public function table(Table $table): Table
    {
        return $this->examsListTable($table);
    }
}
