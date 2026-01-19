<?php

namespace App\Filament\Teacher\Pages\Courses;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamAttempt;
use App\Domain\Training\Models\ExamQuestion;
use App\Support\Helpers\MultilingualHelper;
use Filament\Actions\Action as PageAction;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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

    /**
     * ✅ بدل statePath('selectedExam') اللي بيعمل مشاكل مع JSON + relationship repeaters
     */
    public array $examForm = [];

    /**
     * grading state
     */
    public array $gradingState = [];

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
            $this->examForm = [];
        }

        if ($this->activeTab !== 'grading') {
            $this->selectedAttempt = null;
            $this->gradingState = [];
        }
    }

    protected function getForms(): array
    {
        return [
            'examBuilderForm',
            'gradingForm',
        ];
    }

    protected function localeKey(): string
    {
        $l = app()->getLocale();
        return in_array($l, ['ar', 'en']) ? $l : 'en';
    }

    /**
     * ✅ يملأ examForm من Exam + Questions (بدون relationship binding)
     */
    protected function fillExamFormFromModel(Exam $exam): void
    {
        $locale = $this->localeKey();

        $this->selectedExam = $exam;

        $this->examForm = [
            'id' => $exam->id,
            'type' => $exam->type,
            'duration_minutes' => $exam->duration_minutes,
            'is_active' => (bool) $exam->is_active,

            // input واحد فقط حسب اللغة الحالية
            'title' => is_array($exam->title) ? ($exam->title[$locale] ?? '') : (string) $exam->title,
            'description' => is_array($exam->description) ? ($exam->description[$locale] ?? '') : (string) $exam->description,

            'questions' => $exam->questions()
                ->orderBy('order')
                ->get()
                ->map(function (ExamQuestion $q) use ($locale) {
                    $question = $q->question ?? [];
                    $questionText = is_array($question) ? ($question[$locale] ?? '') : (string) $question;

                    return [
                        'id' => $q->id,
                        'type' => $q->type,
                        'question_text' => $questionText,
                        'points' => (float) ($q->points ?? 0),
                        'order' => (int) ($q->order ?? 0),
                        'required' => (bool) ($q->required ?? true),

                        // options stored as array
                        'options' => collect($q->options ?? [])
                            ->map(function ($opt) use ($locale) {
                                // دعم الشكلين: text_ar/text_en أو text[ar/en]
                                $text = '';
                                if (is_array($opt)) {
                                    if (isset($opt['text']) && is_array($opt['text'])) {
                                        $text = $opt['text'][$locale] ?? '';
                                    } else {
                                        $text = $locale === 'ar' ? ($opt['text_ar'] ?? '') : ($opt['text_en'] ?? '');
                                    }
                                }

                                return [
                                    'text' => $text,
                                    'is_correct' => (bool) ($opt['is_correct'] ?? false),
                                    'order' => (int) ($opt['order'] ?? 0),
                                ];
                            })
                            ->values()
                            ->all(),
                    ];
                })
                ->all(),
        ];

        $this->examBuilderForm->fill($this->examForm);
    }

    protected function newExamDefaults(): void
    {
        $this->selectedExam = null;

        $this->examForm = [
            'id' => null,
            'type' => 'mcq',
            'duration_minutes' => null,
            'is_active' => true,
            'title' => '',
            'description' => '',
            'questions' => [],
        ];

        $this->examBuilderForm->fill($this->examForm);
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
                    ->color(fn ($state) => match ($state) {
                        'mcq' => 'info',
                        'essay' => 'warning',
                        'mixed' => 'success',
                        default => 'gray',
                    })
                    ->label(__('exams.type')),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label(__('exam_center.questions_count')),

                Tables\Columns\TextColumn::make('attempts_count')
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
            ->actions([
                Action::make('edit')
                    ->label(__('exam_center.edit'))
                    ->icon('heroicon-o-pencil')
                    ->action(function (Exam $record) {
                        $record = $record->fresh();
                        $this->activeTab = 'exam_builder';
                        $this->fillExamFormFromModel($record);
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
                        $this->activeTab = 'exam_builder';
                        $this->newExamDefaults();
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
                    ->color(fn ($state) => match ($state) {
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
                        Forms\Components\DatePicker::make('started_from')->label(__('filters.date_from')),
                        Forms\Components\DatePicker::make('started_until')->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['started_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('started_at', '>=', $date))
                            ->when($data['started_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('started_at', '<=', $date));
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
                        $this->activeTab = 'grading';
                    }),

                Action::make('view')
                    ->label(__('exam_center.view'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn () => __('exam_center.attempt_details'))
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

    public function examBuilderForm(Forms\Form $form): Forms\Form
    {
        $locale = $this->localeKey();

        return $form
            ->schema([
                Forms\Components\Section::make(__('exam_center.exam_details'))
                    ->schema([
                        // ✅ input واحد حسب اللغة الحالية
                        Forms\Components\TextInput::make('title')
                            ->label($locale === 'ar' ? __('exams.title_ar') : __('exams.title_en'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label($locale === 'ar' ? __('exams.description_ar') : __('exams.description_en'))
                            ->rows(3),

                        Forms\Components\Select::make('type')
                            ->options([
                                'mcq' => __('exams.type_options.mcq'),
                                'essay' => __('exams.type_options.essay'),
                                'mixed' => __('exams.type_options.mixed'),
                            ])
                            ->required()
                            ->label(__('exams.type')),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->label(__('exams.duration_minutes')),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('exams.is_active'))
                            ->default(true),

                        Forms\Components\Placeholder::make('total_score_preview')
                            ->label(__('exams.total_grade'))
                            ->content(function () {
                                $questions = $this->examBuilderForm?->getState()['questions'] ?? [];
                                $sum = collect($questions)->sum(fn ($q) => (float)($q['points'] ?? 0));
                                return number_format($sum, 2);
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('exam_center.questions'))
                    ->schema([
                        Forms\Components\Repeater::make('questions')
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
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state === 'true_false') {
                                            $set('options', [
                                                ['text' => __('exam_questions.true'), 'is_correct' => true, 'order' => 1],
                                                ['text' => __('exam_questions.false'), 'is_correct' => false, 'order' => 2],
                                            ]);
                                        }

                                        if (!in_array($state, ['mcq', 'true_false'])) {
                                            $set('options', []);
                                        }
                                    }),

                                // ✅ سؤال واحد (مش لغتين) حسب لغة لوحة التحكم
                                Forms\Components\Textarea::make('question_text')
                                    ->label(__('exam_questions.question'))
                                    ->required()
                                    ->rows(3),

                                Forms\Components\TextInput::make('points')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->label(__('exam_questions.points')),

                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->default(0)
                                    ->label(__('exam_questions.order')),

                                Forms\Components\Toggle::make('required')
                                    ->label(__('exam_questions.required'))
                                    ->default(true),

                                // ✅ Options: نفس اللغة الحالية فقط
                                Forms\Components\Repeater::make('options')
                                    ->schema([
                                        Forms\Components\TextInput::make('text')
                                            ->label(__('exam_questions.option_text'))
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
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->visible(fn ($get) => in_array($get('type'), ['mcq', 'true_false']))
                                    ->label(__('exam_questions.options')),
                            ])
                            ->columns(2)
                            ->itemLabel(function ($state) {
                                if (is_array($state)) {
                                    return ($state['question_text'] ?? '') ?: __('exam_center.question');
                                }
                                return __('exam_center.question');
                            })
                            ->addActionLabel(__('exam_center.add_question'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->label(__('exam_center.questions')),
                    ]),
            ])
            ->statePath('examForm');
    }

    /**
     * ✅ حفظ exam + questions يدويًا (ده يمنع errors بتاعت indirect modification)
     */
    public function saveExam(): void
    {
        $this->examBuilderForm->validate();
        $data = $this->examBuilderForm->getState();
        $locale = $this->localeKey();

        DB::transaction(function () use ($data, $locale) {
            $examId = $data['id'] ?? null;

            if ($examId) {
                $exam = Exam::query()
                    ->where('id', $examId)
                    ->where('course_id', $this->record->id)
                    ->firstOrFail();
            } else {
                $exam = new Exam();
                $exam->course_id = $this->record->id;
            }

            // merge title/description into JSON by locale (بدون فرض لغتين)
            $currentTitle = is_array($exam->title) ? $exam->title : [];
            $currentTitle[$locale] = (string) ($data['title'] ?? '');

            $currentDesc = is_array($exam->description) ? $exam->description : [];
            $currentDesc[$locale] = (string) ($data['description'] ?? '');

            $exam->fill([
                'title' => $currentTitle,
                'description' => $currentDesc,
                'type' => $data['type'],
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'is_active' => (bool)($data['is_active'] ?? true),
            ]);

            $exam->save();

            // questions
            $incomingQuestions = collect($data['questions'] ?? [])->values();

            $keptIds = [];

            foreach ($incomingQuestions as $index => $qData) {
                $qid = $qData['id'] ?? null;

                $question = null;
                if ($qid) {
                    $question = ExamQuestion::query()
                        ->where('id', $qid)
                        ->where('exam_id', $exam->id)
                        ->first();
                }

                if (!$question) {
                    $question = new ExamQuestion();
                    $question->exam_id = $exam->id;
                }

                // merge question_text into JSON by locale
                $currentQuestion = is_array($question->question) ? $question->question : [];
                $currentQuestion[$locale] = (string)($qData['question_text'] ?? '');

                // options: نخزن النص حسب locale فقط (ونسيب اللغة الأخرى زي ما هي)
                $options = collect($qData['options'] ?? [])
                    ->map(function ($opt) use ($locale, $question) {
                        $text = (string)($opt['text'] ?? '');

                        // حافظ على الشكل المرن: نخزن text كـ array (أفضل)
                        return [
                            'text' => [
                                'ar' => $locale === 'ar' ? $text : ((is_array($opt['text'] ?? null) ? ($opt['text']['ar'] ?? '') : '') ?: ''),
                                'en' => $locale === 'en' ? $text : ((is_array($opt['text'] ?? null) ? ($opt['text']['en'] ?? '') : '') ?: ''),
                            ],
                            'is_correct' => (bool)($opt['is_correct'] ?? false),
                            'order' => (int)($opt['order'] ?? 0),
                        ];
                    })
                    ->values()
                    ->all();

                $question->fill([
                    'type' => $qData['type'],
                    'question' => $currentQuestion,
                    'options' => $options,
                    'points' => (float)($qData['points'] ?? 0),
                    'order' => (int)($qData['order'] ?? $index),
                    'required' => (bool)($qData['required'] ?? true),
                    'is_active' => true,
                ]);

                $question->save();
                $keptIds[] = $question->id;
            }

            // delete removed questions
            ExamQuestion::query()
                ->where('exam_id', $exam->id)
                ->when(count($keptIds) > 0, fn ($q) => $q->whereNotIn('id', $keptIds))
                ->when(count($keptIds) === 0, fn ($q) => $q) // delete all if empty
                ->delete();

            // total score
            $totalScore = (float) ExamQuestion::query()
                ->where('exam_id', $exam->id)
                ->sum('points');

            $exam->update(['total_score' => $totalScore]);

            // refresh UI state
            $this->fillExamFormFromModel($exam->fresh());
        });

        Notification::make()
            ->title(__('exam_center.saved'))
            ->success()
            ->send();
    }

    public function gradingForm(Forms\Form $form): Forms\Form
    {
        if (!$this->selectedAttempt) {
            return $form->schema([
                Forms\Components\Placeholder::make('no_attempt')
                    ->label('')
                    ->content(__('exam_center.select_attempt_to_grade')),
            ])->statePath('gradingState');
        }

        $attempt = $this->selectedAttempt->load(['answers.question', 'exam.questions']);
        $questions = $attempt->exam->questions()->orderBy('order')->get();

        $schema = [];

        foreach ($questions as $question) {
            $answer = $attempt->answers->firstWhere('question_id', $question->id);

            $schema[] = Forms\Components\Section::make($question->order . '. ' . MultilingualHelper::formatMultilingualField($question->question))
                ->schema([
                    Forms\Components\Placeholder::make("qtype_{$question->id}")
                        ->label(__('exam_questions.type'))
                        ->content(__('exam_questions.type_options.' . $question->type)),

                    Forms\Components\Placeholder::make("pp_{$question->id}")
                        ->label(__('exam_center.points_possible'))
                        ->content($question->points),

                    Forms\Components\Placeholder::make("ans_{$question->id}")
                        ->label(__('exam_center.student_answer'))
                        ->content(function () use ($answer, $question) {
                            if (!$answer) {
                                return __('exam_center.no_answer');
                            }

                            if (in_array($question->type, ['mcq', 'true_false'])) {
                                $options = $question->options ?? [];
                                $selected = collect($options)->firstWhere('order', $answer->answer);

                                if ($selected && isset($selected['text']) && is_array($selected['text'])) {
                                    return MultilingualHelper::formatMultilingualField($selected['text']);
                                }

                                return (string) ($answer->answer ?? __('exam_center.no_answer'));
                            }

                            return (string) ($answer->answer ?? __('exam_center.no_answer'));
                        }),

                    ...(in_array($question->type, ['essay', 'short_answer']) ? [
                        Forms\Components\TextInput::make("answers.{$question->id}.points_earned")
                            ->label(__('grading.points_earned'))
                            ->numeric()
                            ->default(fn () => $answer?->points_earned ?? 0)
                            ->minValue(0)
                            ->maxValue($question->points)
                            ->required(),

                        Forms\Components\Textarea::make("answers.{$question->id}.feedback")
                            ->label(__('grading.feedback'))
                            ->rows(3)
                            ->default(fn () => $answer?->feedback ?? ''),
                    ] : [
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
            ->schema($schema)
            ->statePath('gradingState');
    }

    protected function checkAnswerCorrectness($answer, $question): bool
    {
        if (!in_array($question->type, ['mcq', 'true_false'])) {
            return false;
        }

        $options = $question->options ?? [];
        $selectedOption = collect($options)->firstWhere('order', $answer->answer);

        return (bool) ($selectedOption['is_correct'] ?? false);
    }

    public function saveGrading(): void
    {
        if (!$this->selectedAttempt) {
            return;
        }

        $this->gradingForm->validate();
        $data = $this->gradingForm->getState();
        $attempt = $this->selectedAttempt->load(['answers', 'exam.questions']);

        DB::transaction(function () use ($data, $attempt) {
            $totalScore = 0;
            $maxScore = (float) $attempt->exam->questions()->sum('points');

            foreach ($attempt->exam->questions as $question) {
                $answer = $attempt->answers->firstWhere('question_id', $question->id);
                if (!$answer) {
                    continue;
                }

                if (in_array($question->type, ['essay', 'short_answer'])) {
                    $pointsEarned = (float)($data['answers'][$question->id]['points_earned'] ?? 0);
                    $feedback = $data['answers'][$question->id]['feedback'] ?? null;

                    $answer->update([
                        'points_earned' => min($pointsEarned, (float)$question->points),
                        'points_possible' => (float)$question->points,
                        'feedback' => $feedback,
                    ]);
                } else {
                    $isCorrect = $this->checkAnswerCorrectness($answer, $question);

                    $overridePoints = $data['answers'][$question->id]['points_earned_override'] ?? null;

                    $pointsEarned = $overridePoints !== null
                        ? min((float)$overridePoints, (float)$question->points)
                        : ($isCorrect ? (float)$question->points : 0.0);

                    $answer->update([
                        'is_correct' => $isCorrect,
                        'points_earned' => $pointsEarned,
                        'points_possible' => (float)$question->points,
                    ]);
                }

                $answer->refresh();
                $totalScore += (float)$answer->points_earned;
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
    public function getKpiStats(): array
    {
        $teacherId = auth('teacher')->id();
        $courseId = $this->record?->id;

        if (!$courseId) {
            return [
                'total_exams' => 0,
                'total_attempts' => 0,
                'pending_grading' => 0,
                'avg_score' => 0,
            ];
        }

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
            'avg_score' => round((float) $avgScore, 1),
        ];
    }

}
