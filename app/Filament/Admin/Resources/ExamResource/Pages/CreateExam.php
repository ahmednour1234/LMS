<?php

namespace App\Filament\Admin\Resources\ExamResource\Pages;

use App\Domain\Training\Models\Lesson;
use App\Filament\Admin\Resources\ExamResource;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    /**
     * هنحتاج نخزن الأسئلة مؤقتًا لأننا هنشيلها من $data قبل الحفظ،
     * وبعدين ننشئها بعد ما الامتحان يتعمل.
     */
    protected array $pendingQuestions = [];

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]) . '?activeTab=questions';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('exam_create_tabs')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('details')
                        ->label(__('exams.details'))
                        ->schema([
                            Forms\Components\Select::make('course_id')
                                ->relationship('course', 'code')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->label(__('exams.course'))
                                ->reactive(),

                            Forms\Components\Select::make('lesson_id')
                                ->relationship('lesson', 'id', function ($query, $get) {
                                    $courseId = $get('course_id');
                                    if ($courseId) {
                                        $query->whereHas('section', fn ($q) => $q->where('course_id', $courseId));
                                    }
                                    return $query->orderBy('id');
                                })
                                ->getOptionLabelUsing(function ($record): string {
                                    if (is_object($record)) {
                                        return MultilingualHelper::formatMultilingualField($record->title) ?: 'N/A';
                                    }
                                    $lesson = Lesson::find($record);
                                    return $lesson ? (MultilingualHelper::formatMultilingualField($lesson->title) ?: 'N/A') : 'N/A';
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->label(__('exams.lesson'))
                                ->visible(fn ($get) => (bool) $get('course_id')),

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
                                ->label(__('exams.type'))
                                ->reactive(),

                            Forms\Components\TextInput::make('duration_minutes')
                                ->numeric()
                                ->label(__('exams.duration_minutes')),

                            Forms\Components\Toggle::make('is_active')
                                ->label(__('exams.is_active'))
                                ->default(true),
                        ]),

                    Forms\Components\Tabs\Tab::make('questions')
                        ->label(__('exams.questions'))
                        ->schema([
                            Forms\Components\Section::make(__('exams.manage_questions'))
                                ->schema([
                                    Forms\Components\Repeater::make('questions')
                                        ->label(__('exams.questions'))
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->cloneable()
                                        ->reorderable() // ترتيب داخل الفورم (هنحفظ order)
                                        ->itemLabel(function (array $state): string {
                                            $order = $state['order'] ?? null;
                                            $pts = $state['points'] ?? 0;
                                            return $order ? "Q{$order} ({$pts})" : __('exams.question');
                                        })
                                        ->schema([
                                            Forms\Components\Grid::make(12)->schema([
                                                Forms\Components\TextInput::make('order')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->label(__('exam_questions.order'))
                                                    ->columnSpan(2)
                                                    ->required(),

                                                Forms\Components\Select::make('type')
                                                    ->options([
                                                        'mcq' => __('exam_questions.type_options.mcq'),
                                                        'true_false' => __('exam_questions.type_options.true_false'),
                                                        'essay' => __('exam_questions.type_options.essay'),
                                                    ])
                                                    ->label(__('exam_questions.type'))
                                                    ->required()
                                                    ->reactive()
                                                    ->columnSpan(4),

                                                Forms\Components\TextInput::make('points')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->label(__('exam_questions.points'))
                                                    ->required()
                                                    ->columnSpan(3),
                                            ]),

                                            Forms\Components\Radio::make('question_lang')
                                                ->label(__('exam_questions.question_language'))
                                                ->options([
                                                    'ar' => __('general.arabic'),
                                                    'en' => __('general.english'),
                                                ])
                                                ->default('en')
                                                ->live()
                                                ->afterStateUpdated(function ($state, $set) {
                                                    if ($state === 'ar') {
                                                        $set('question.en', null);
                                                    } else {
                                                        $set('question.ar', null);
                                                    }
                                                }),

                                            Forms\Components\Textarea::make('question.ar')
                                                ->label(__('exam_questions.question_ar'))
                                                ->required(fn ($get) => $get('question_lang') === 'ar')
                                                ->visible(fn ($get) => $get('question_lang') === 'ar')
                                                ->dehydrated(fn ($get) => $get('question_lang') === 'ar')
                                                ->rows(3),

                                            Forms\Components\Textarea::make('question.en')
                                                ->label(__('exam_questions.question_en'))
                                                ->required(fn ($get) => $get('question_lang') === 'en')
                                                ->visible(fn ($get) => $get('question_lang') === 'en')
                                                ->dehydrated(fn ($get) => $get('question_lang') === 'en')
                                                ->rows(3),

                                            // MCQ OPTIONS
                                            Forms\Components\Repeater::make('options')
                                                ->label(__('exam_questions.options'))
                                                ->visible(fn ($get) => ($get('type') ?? '') === 'mcq')
                                                ->defaultItems(4)
                                                ->schema([
                                                    Forms\Components\TextInput::make('ar')
                                                        ->label(__('exam_questions.option_ar'))
                                                        ->maxLength(255),
                                                    Forms\Components\TextInput::make('en')
                                                        ->label(__('exam_questions.option_en'))
                                                        ->required()
                                                        ->maxLength(255),
                                                ])
                                                ->reactive(),

                                            // True/False
                                            Forms\Components\Radio::make('correct_answer')
                                                ->label(__('exam_questions.correct_answer'))
                                                ->options([
                                                    1 => __('exam_questions.true_false_true'),
                                                    0 => __('exam_questions.true_false_false'),
                                                ])
                                                ->visible(fn ($get) => ($get('type') ?? '') === 'true_false')
                                                ->required(fn ($get) => ($get('type') ?? '') === 'true_false'),

                                            // MCQ correct answer as Select
                                            Forms\Components\Select::make('correct_answer')
                                                ->label(__('exam_questions.correct_answer'))
                                                ->options(function ($get) {
                                                    $options = $get('options') ?? [];
                                                    $opts = [];
                                                    foreach ($options as $index => $option) {
                                                        if (is_array($option)) {
                                                            $text = $option['en'] ?? $option['ar'] ?? "Option " . ($index + 1);
                                                        } else {
                                                            $text = "Option " . ($index + 1);
                                                        }
                                                        $opts[$index] = $text;
                                                    }
                                                    return $opts;
                                                })
                                                ->visible(fn ($get) => ($get('type') ?? '') === 'mcq')
                                                ->required(fn ($get) => ($get('type') ?? '') === 'mcq')
                                                ->reactive(),
                                        ]),
                                ]),
                        ]),
                ])
                ->persistTabInQueryString('activeTab'),
        ]);
    }

    /**
     * قبل ما الـ Exam يتعمل: نفصل questions من الداتا ونخزنها مؤقتًا
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingQuestions = $data['questions'] ?? [];
        unset($data['questions']);

        // هنحسب total_score من الأسئلة (لو عندك column total_score)
        $sum = 0;
        foreach ($this->pendingQuestions as $q) {
            $sum += (float) ($q['points'] ?? 0);
        }
        $data['total_score'] = $sum;

        return $data;
    }

    /**
     * بعد إنشاء الـ Exam: ننشئ الأسئلة في العلاقة questions()
     */
    protected function afterCreate(): void
    {
        if (empty($this->pendingQuestions)) {
            return;
        }

        // تأكد إن عندك $this->record->questions() علاقة موجودة
        foreach ($this->pendingQuestions as $q) {
            $type = $q['type'] ?? 'mcq';

            // Process question - only save selected language
            $questionLang = $q['question_lang'] ?? 'en';
            $question = [];
            $questionData = $q['question'] ?? [];
            if (is_array($questionData)) {
                if ($questionLang === 'ar' && isset($questionData['ar']) && !empty($questionData['ar'])) {
                    $question = ['ar' => $questionData['ar']];
                } elseif ($questionLang === 'en' && isset($questionData['en']) && !empty($questionData['en'])) {
                    $question = ['en' => $questionData['en']];
                }
            }

            // MCQ options in canonical format [{"ar":"...","en":"..."}]
            $options = null;
            $correctAnswer = null;
            if ($type === 'mcq' && isset($q['options']) && is_array($q['options'])) {
                $processedOptions = [];
                foreach ($q['options'] as $opt) {
                    if (!is_array($opt)) {
                        continue;
                    }
                    $ar = $opt['ar'] ?? '';
                    $en = $opt['en'] ?? '';
                    if ($en || $ar) {
                        $processedOptions[] = [
                            'ar' => $ar ?: '',
                            'en' => $en ?: '',
                        ];
                    }
                }
                if (!empty($processedOptions)) {
                    $options = $processedOptions;
                }
                
                // Ensure correct_answer is integer
                $correctAnswer = isset($q['correct_answer']) ? (int) $q['correct_answer'] : null;
                if ($correctAnswer !== null && ($correctAnswer < 0 || $correctAnswer >= count($processedOptions))) {
                    $correctAnswer = null;
                }
            } elseif ($type === 'true_false') {
                $correctAnswer = isset($q['correct_answer']) ? (int) $q['correct_answer'] : null;
            }

            $this->record->questions()->create([
                'type'           => $type,
                'question'       => $question,
                'points'         => (float) ($q['points'] ?? 0),
                'options'        => $options,
                'correct_answer' => $correctAnswer,
                'order'          => (int) ($q['order'] ?? 0),
            ]);
        }

        Notification::make()
            ->title(__('exams.questions_added_successfully'))
            ->success()
            ->send();
    }
}
