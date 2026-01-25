<?php

namespace App\Filament\Teacher\Resources\Training\StudentExamAttemptResource\Pages;

use App\Domain\Training\Models\ExamAnswer;
use App\Filament\Teacher\Resources\Training\StudentExamAttemptResource;
use App\Support\Helpers\MultilingualHelper;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewStudentExamAttempt extends ViewRecord
{
    protected static string $resource = StudentExamAttemptResource::class;

    /** Filament form state */
    public ?array $data = [];

    public float $calculatedScore = 0;
    public float $calculatedMaxScore = 0;
    public float $calculatedPercentage = 0;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->form->fill($this->getFillData());
        $this->recalculateTotals();
    }

    protected function getFillData(): array
    {
        $this->record->load([
            'student',
            'exam',
            'answers.question',
        ]);

        return [
            'student_name' => $this->record->student->name ?? '',
            'exam_title'   => MultilingualHelper::formatMultilingualField($this->record->exam->title ?? []),
            'attempt_no'   => $this->record->attempt_no ?? null,
            'status'       => $this->record->status,
            'started_at'   => $this->formatDateTime($this->record->started_at),
            'submitted_at' => $this->formatDateTime($this->record->submitted_at),

            'answers' => $this->record->answers->map(function (ExamAnswer $answer) {
                $q = $answer->question;

                return [
                    'id'             => $answer->id,
                    'question_id'     => $answer->question_id,
                    'answer'          => $answer->answer,
                    'answer_text'     => $answer->answer_text,
                    'selected_option' => $answer->selected_option,

                    // IMPORTANT: We will use points_earned (because your table has points_earned)
                    'points_awarded'  => (float) ($answer->points_earned ?? 0),
                    'feedback'        => $answer->feedback,

                    'question_data' => $q ? [
                        'type'           => $q->type,
                        'question'       => $q->question,
                        'options'        => $q->options,
                        'correct_answer' => $q->correct_answer,
                        'points'         => (float) ($q->points ?? 0),
                    ] : null,
                ];
            })->values()->toArray(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('exams.save_all_grades'))
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'graded')
                ->action(fn () => $this->saveGrades()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make(__('exams.attempt_information'))
                    ->schema([
                        Forms\Components\TextInput::make('student_name')
                            ->label(__('exams.student'))
                            ->disabled(),

                        Forms\Components\TextInput::make('exam_title')
                            ->label(__('exams.exam'))
                            ->disabled(),

                        Forms\Components\TextInput::make('attempt_no')
                            ->label(__('exams.attempt_no'))
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label(__('exams.status'))
                            ->options([
                                'in_progress' => __('exams.status.in_progress'),
                                'submitted'   => __('exams.status.submitted'),
                                'graded'      => __('exams.status.graded'),
                            ])
                            ->disabled(fn () => $this->record->status === 'graded')
                            ->reactive(),

                        Forms\Components\Placeholder::make('score_display')
                            ->label(__('exams.score'))
                            ->content(fn () => sprintf(
                                '%s / %s (%.2f%%)',
                                number_format($this->calculatedScore, 2),
                                number_format($this->calculatedMaxScore, 2),
                                $this->calculatedPercentage
                            )),

                        Forms\Components\TextInput::make('started_at')
                            ->label(__('exams.started_at'))
                            ->disabled(),

                        Forms\Components\TextInput::make('submitted_at')
                            ->label(__('exams.submitted_at'))
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('exams.questions_answers'))
                    ->schema([
                        Forms\Components\Repeater::make('answers')
                            ->schema([
                                Forms\Components\Placeholder::make('question_display')
                                    ->label(__('exams.question'))
                                    ->content(function (Forms\Get $get) {
                                        $q = $get('question_data');
                                        if (!$q || !isset($q['question'])) return '';

                                        return new HtmlString(
                                            '<div class="text-base font-medium text-gray-900 dark:text-gray-100 whitespace-pre-wrap">' .
                                            nl2br(e(MultilingualHelper::formatMultilingualField($q['question']))) .
                                            '</div>'
                                        );
                                    }),

                                Forms\Components\Placeholder::make('type_display')
                                    ->label(__('exams.type'))
                                    ->content(function (Forms\Get $get) {
                                        $type = $get('question_data.type');
                                        return $type ? __('exams.type_options.' . $type) : '';
                                    }),

                                Forms\Components\Placeholder::make('options_display')
                                    ->label(__('exams.options'))
                                    ->visible(fn (Forms\Get $get) => (($get('question_data.type') ?? '') === 'mcq'))
                                    ->content(function (Forms\Get $get) {
                                        $q = $get('question_data');
                                        $options = $q['options'] ?? [];
                                        if (!is_array($options) || empty($options)) return '';

                                        $correct = $q['correct_answer'] ?? null;

                                        $html = '<div class="space-y-1">';
                                        foreach ($options as $idx => $opt) {
                                            $text = is_array($opt) ? ($opt['text'] ?? '') : (string) $opt;
                                            $isCorrect = ($correct !== null && (string)$idx === (string)$correct);

                                            $html .= '<div class="' . ($isCorrect ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-700 dark:text-gray-300') . '">'
                                                . ($isCorrect ? '✓ ' : '')
                                                . ($idx + 1) . '. ' . e($text)
                                                . '</div>';
                                        }
                                        $html .= '</div>';

                                        return new HtmlString($html);
                                    }),

                                Forms\Components\Placeholder::make('student_answer_display')
                                    ->label(__('exams.student_answer'))
                                    ->content(function (Forms\Get $get) {
                                        $type = $get('question_data.type');

                                        // Essay
                                        if ($type === 'essay') {
                                            $answerText = $get('answer_text');
                                            if (!$answerText) return __('exams.no_answer');

                                            return new HtmlString(
                                                '<div class="text-base text-gray-900 dark:text-gray-100 whitespace-pre-wrap bg-gray-50 dark:bg-gray-800 p-3 rounded border">' .
                                                nl2br(e($answerText)) .
                                                '</div>'
                                            );
                                        }

                                        // MCQ
                                        if ($type === 'mcq') {
                                            $q = $get('question_data');
                                            $options = $q['options'] ?? [];
                                            $selected = $get('selected_option') ?? $get('answer');

                                            $selectedIndex = $this->normalizeSelectedOptionToIndex($selected, $options);
                                            if ($selectedIndex === null || !isset($options[$selectedIndex])) {
                                                return __('exams.no_answer');
                                            }

                                            $opt = $options[$selectedIndex];
                                            $text = is_array($opt) ? ($opt['text'] ?? '') : (string) $opt;

                                            $isCorrect = $this->isMcqCorrect([
                                                'selected_option' => $selected,
                                                'answer' => $get('answer'),
                                            ], $q);

                                            $color = $isCorrect ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';

                                            return new HtmlString(
                                                '<div class="' . $color . ' font-medium">' .
                                                ($selectedIndex + 1) . '. ' . e($text) .
                                                ($isCorrect
                                                    ? ' <span class="text-xs">(' . __('exams.correct') . ')</span>'
                                                    : ' <span class="text-xs">(' . __('exams.incorrect') . ')</span>'
                                                ) .
                                                '</div>'
                                            );
                                        }

                                        return __('exams.no_answer');
                                    }),

                                // Teacher grading: ONLY editable for essay (and not graded)
                                Forms\Components\TextInput::make('points_awarded')
                                    ->label(__('exams.points_awarded'))
                                    ->numeric()
                                    ->default(0)
                                    ->suffix(fn (Forms\Get $get) => '/' . (float) ($get('question_data.points') ?? 0))
                                    ->maxValue(fn (Forms\Get $get) => (float) ($get('question_data.points') ?? 0))
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->disabled(fn (Forms\Get $get) =>
                                        $this->record->status === 'graded' ||
                                        (($get('question_data.type') ?? '') !== 'essay')
                                    )
                                    ->reactive()
                                    ->afterStateUpdated(fn () => $this->recalculateTotals())
                                    ->dehydrated(true)
                                    ->live(onBlur: true),

                                Forms\Components\Textarea::make('feedback')
                                    ->label(__('exams.feedback'))
                                    ->rows(3)
                                    ->visible(fn (Forms\Get $get) => (($get('question_data.type') ?? '') === 'essay'))
                                    ->disabled(fn () => $this->record->status === 'graded'),
                            ])
                            ->collapsible()
                            ->reorderable(false)
                            ->deletable(false)
                            ->addable(false) // ✅ removes "Add to answers"
                            ->itemLabel(function (array $state) {
                                $q = $state['question_data']['question'] ?? null;
                                if ($q) {
                                    $text = strip_tags(MultilingualHelper::formatMultilingualField($q));
                                    return mb_substr($text, 0, 60) . '...';
                                }
                                return __('exams.answer');
                            }),
                    ]),
            ]);
    }

    protected function saveGrades(): void
    {
        $answers = $this->data['answers'] ?? [];

        $total = 0;
        $max = 0;

        foreach ($answers as $row) {
            if (empty($row['id'])) continue;

            $answer = ExamAnswer::query()->with('question')->find($row['id']);
            if (!$answer) continue;

            $q = $answer->question;
            $qPoints = (float) ($q?->points ?? 0);
            $max += $qPoints;

            $awarded = (float) ($row['points_awarded'] ?? 0);
            if ($awarded < 0) $awarded = 0;
            if ($awarded > $qPoints) $awarded = $qPoints;

            // Save only essay grading (MCQ remains unchanged)
            if (($q?->type ?? null) === 'essay') {
                $answer->points_earned = $awarded;
                $answer->feedback = $row['feedback'] ?? null;
                $answer->save();
            }

            $total += (float) ($answer->points_earned ?? 0);
        }

        $this->calculatedScore = $total;
        $this->calculatedMaxScore = $max;
        $this->calculatedPercentage = $max > 0 ? ($total / $max) * 100 : 0;

        $this->record->score = $this->calculatedScore;
        $this->record->max_score = $this->calculatedMaxScore;
        $this->record->percentage = $this->calculatedPercentage;

        $this->record->save();

        Notification::make()
            ->title(__('exams.grades_saved'))
            ->success()
            ->send();

        $this->form->fill($this->getFillData());
        $this->recalculateTotals();
    }

    protected function recalculateTotals(): void
    {
        $answers = $this->data['answers'] ?? [];

        $total = 0;
        $max = 0;

        foreach ($answers as $row) {
            $q = $row['question_data'] ?? null;
            $qPoints = (float) ($q['points'] ?? 0);
            $max += $qPoints;

            // total uses current state points_awarded (for essay) + existing points_earned for mcq from DB fill
            $awarded = (float) ($row['points_awarded'] ?? 0);
            if ($awarded < 0) $awarded = 0;
            if ($awarded > $qPoints) $awarded = $qPoints;

            $total += $awarded;
        }

        $this->calculatedScore = $total;
        $this->calculatedMaxScore = $max;
        $this->calculatedPercentage = $max > 0 ? ($total / $max) * 100 : 0;
    }

    protected function isMcqCorrect(array $answerRow, array $questionData): bool
    {
        $options = $questionData['options'] ?? [];
        $correct = $questionData['correct_answer'] ?? null;

        $selected = $answerRow['selected_option'] ?? ($answerRow['answer'] ?? null);
        $selectedIndex = $this->normalizeSelectedOptionToIndex($selected, $options);

        if ($selectedIndex === null || $correct === null) return false;

        return (string) $selectedIndex === (string) $correct;
    }

    protected function normalizeSelectedOptionToIndex($selected, array $options): ?int
    {
        if ($selected === null || $selected === '') return null;

        if (is_string($selected) && preg_match('/^[A-Za-z]$/', $selected)) {
            $idx = ord(strtoupper($selected)) - ord('A');
            return ($idx >= 0) ? $idx : null;
        }

        if (is_numeric($selected)) {
            $n = (int) $selected;

            // assume 1-based if within options count
            if ($n >= 1 && $n <= count($options)) {
                return $n - 1;
            }

            return $n;
        }

        return null;
    }

    protected function formatDateTime($state): string
    {
        if (!$state) return '';
        if (is_string($state)) return $state;
        if (is_object($state) && method_exists($state, 'format')) {
            return $state->format('Y-m-d H:i:s');
        }
        return (string) $state;
    }
}
