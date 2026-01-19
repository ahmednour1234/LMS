<?php

namespace App\Filament\Teacher\Pages\Courses;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamAttempt;
use App\Domain\Training\Models\ExamQuestion;
use App\Support\Helpers\MultilingualHelper;
use Filament\Actions\Action as PageAction;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CourseExamCenterPage extends Page implements Forms\Contracts\HasForms, HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

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
     * IMPORTANT:
     * - نخلي الفورم state في array منفصل بدل statePath على Model مباشرة
     *   عشان نمنع Indirect modification errors.
     */
    public array $examFormData = [];
    public array $gradingFormData = [];

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
            $this->examFormData = [];
        }

        if ($this->activeTab !== 'grading') {
            $this->selectedAttempt = null;
            $this->gradingFormData = [];
        }
    }

    /**
     * لازم تكون public علشان Blade/Livewire يقدر يناديها.
     */
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
            ->whereHas('answers.question', fn ($q) => $q->whereIn('type', ['essay', 'short_answer']))
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

    public function table(Table $table): Table
    {
        return $this->examsListTable($table);
    }

    public function getExamsListTableProperty(): Table
    {
        return $this->examsListTable($this->makeTable());
    }

    public function getAttemptsTableProperty(): Table
    {
        return $this->attemptsTable($this->makeTable());
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
                        $this->selectedExam = $record->fresh()->load('questions');
                        $this->examFormData = [
                            'id' => $this->selectedExam->id,
                            'title' => $this->selectedExam->title ?? ['ar' => '', 'en' => ''],
                            'description' => $this->selectedExam->description ?? ['ar' => '', 'en' => ''],
                            'type' => $this->selectedExam->type,
                            'duration_minutes' => $this->selectedExam->duration_minutes,
                            'is_active' => (bool) $this->selectedExam->is_active,
                            'questions' => $this->selectedExam->questions()
                                ->orderBy('order')
                                ->get()
                                ->map(fn ($q) => [
                                    'id' => $q->id,
                                    'type' => $q->type,
                                    'question' => $q->question ?? ['ar' => '', 'en' => ''],
                                    'points' => $q->points ?? 0,
                                    'order' => $q->order ?? 0,
                                    'required' => (bool) $q->required,
                                    'options' => is_array($q->options) ? $q->options : [],
                                ])->toArray(),
                        ];

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
                        $this->selectedExam = null;
                        $this->examFormData = [
                            'id' => null,
                            'title' => ['ar' => '', 'en' => ''],
                            'description' => ['ar' => '', 'en' => ''],
                            'type' => 'mcq',
                            'duration_minutes' => null,
                            'is_active' => true,
                            'questions' => [],
                        ];

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

                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exam_center.submitted_at')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('attempts.status.' . $state))
                    ->label(__('attempts.status_label')),

                Tables\Columns\TextColumn::make('percentage')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1) . '%' : '-')
                    ->label(__('exam_center.percentage')),
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

                        $this->gradingFormData = []; // هيتعمر من الفورم
                        $this->activeTab = 'grading';
                    }),

                Action::make('view')
                    ->label(__('exam_center.view'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn () => __('exam_center.attempt_details'))
                    ->modalContent(fn ($record) => view('filament.teacher.modals.exam-attempt-details', ['attempt' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('exam_center.close')),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public function examBuilderForm(Forms\Form $form): Forms\Form
    {
        return $form
            ->statePath('examFormData')
            ->schema([
                Forms\Components\Section::make(__('exam_center.exam_details'))
                    ->schema([
                        Forms\Components\TextInput::make('title.ar')
                            ->label(__('exams.title_ar'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('title.en')
                            ->label(__('exams.title_en'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description.ar')
                            ->label(__('exams.description_ar'))
                            ->rows(3),

                        Forms\Components\Textarea::make('description.en')
                            ->label(__('exams.description_en'))
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
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // ✅ أهم سطر: لو النوع مش options-based امسح options
                                        if (!in_array($state, ['mcq', 'true_false'], true)) {
                                            $set('options', []);
                                            return;
                                        }

                                        if ($state === 'true_false') {
                                            $set('options', ExamQuestion::getTrueFalseOptions());
                                        } else {
                                            // mcq: خليها فاضية واليوزر يضيف
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

                                /**
                                 * ✅ FIX: options only validate + dehydrate for mcq/true_false
                                 */
                                Forms\Components\Repeater::make('options')
                                    ->label(__('exam_questions.options'))
                                    ->visible(fn (Get $get) => in_array($get('type'), ['mcq', 'true_false'], true))
                                    ->dehydrated(fn (Get $get) => in_array($get('type'), ['mcq', 'true_false'], true))
                                    ->defaultItems(0)
                                    ->schema([
                                        Forms\Components\TextInput::make('text_ar')
                                            ->label(__('exam_center.option_text_ar'))
                                            ->maxLength(255)
                                            ->required(fn (Get $get) => in_array($get('../../type'), ['mcq', 'true_false'], true)),

                                        Forms\Components\TextInput::make('text_en')
                                            ->label(__('exam_center.option_text_en'))
                                            ->maxLength(255)
                                            ->required(fn (Get $get) => in_array($get('../../type'), ['mcq', 'true_false'], true)),

                                        Forms\Components\Toggle::make('is_correct')
                                            ->label(__('exam_center.is_correct'))
                                            ->default(false),

                                        Forms\Components\TextInput::make('order')
                                            ->numeric()
                                            ->default(0)
                                            ->label(__('exam_questions.order')),
                                    ])
                                    ->columns(4),
                            ])
                            ->columns(2)
                            ->addActionLabel(__('exam_center.add_question'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable(),
                    ]),
            ]);
    }

    public function saveExam(): void
    {
        $data = $this->examBuilderForm->getState();

        DB::transaction(function () use ($data) {
            $teacherId = auth('teacher')->id();

            // تأمين: الكورس بتاع المدرس
            abort_unless($this->record?->owner_teacher_id === $teacherId, 404);

            if (!empty($data['id'])) {
                $exam = Exam::query()
                    ->where('id', $data['id'])
                    ->where('course_id', $this->record->id)
                    ->firstOrFail();

                $exam->update([
                    'course_id' => $this->record->id,
                    'title' => $data['title'] ?? ['ar' => '', 'en' => ''],
                    'description' => $data['description'] ?? ['ar' => '', 'en' => ''],
                    'type' => $data['type'],
                    'duration_minutes' => $data['duration_minutes'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
            } else {
                $exam = Exam::create([
                    'course_id' => $this->record->id,
                    'title' => $data['title'] ?? ['ar' => '', 'en' => ''],
                    'description' => $data['description'] ?? ['ar' => '', 'en' => ''],
                    'type' => $data['type'],
                    'duration_minutes' => $data['duration_minutes'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
            }

            $questionIds = [];

            foreach (($data['questions'] ?? []) as $index => $q) {
                $type = $q['type'] ?? 'mcq';

                // ✅ options cleanup: لو مش mcq/true_false خليه []
                $options = in_array($type, ['mcq', 'true_false'], true)
                    ? array_values(array_filter(($q['options'] ?? []), function ($opt) {
                        $ar = trim((string) ($opt['text_ar'] ?? ''));
                        $en = trim((string) ($opt['text_en'] ?? ''));
                        return $ar !== '' || $en !== '';
                    }))
                    : [];

                $payload = [
                    'exam_id' => $exam->id,
                    'type' => $type,
                    'question' => $q['question'] ?? ['ar' => '', 'en' => ''],
                    'options' => $options,
                    'points' => (float) ($q['points'] ?? 0),
                    'order' => (int) ($q['order'] ?? $index),
                    'required' => (bool) ($q['required'] ?? true),
                    'is_active' => true,
                ];

                if (!empty($q['id'])) {
                    $model = ExamQuestion::query()
                        ->where('id', $q['id'])
                        ->where('exam_id', $exam->id)
                        ->first();

                    if ($model) {
                        $model->update($payload);
                        $questionIds[] = $model->id;
                        continue;
                    }
                }

                $created = ExamQuestion::create($payload);
                $questionIds[] = $created->id;
            }

            ExamQuestion::query()
                ->where('exam_id', $exam->id)
                ->when(count($questionIds) > 0, fn ($q) => $q->whereNotIn('id', $questionIds))
                ->delete();

            $totalScore = (float) $exam->questions()->sum('points');
            $exam->update(['total_score' => $totalScore]);

            $this->selectedExam = $exam->fresh()->load('questions');
            $this->examFormData['id'] = $this->selectedExam->id;
        });

        Notification::make()
            ->title(__('exam_center.saved'))
            ->success()
            ->send();
    }
}
