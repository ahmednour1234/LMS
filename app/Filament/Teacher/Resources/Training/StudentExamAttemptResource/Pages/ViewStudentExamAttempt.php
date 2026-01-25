<?php

namespace App\Filament\Teacher\Resources\Training\StudentExamAttemptResource\Pages;

use App\Domain\Training\Models\ExamAttempt;
use App\Filament\Teacher\Resources\Training\StudentExamAttemptResource;
use App\Support\Helpers\MultilingualHelper;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentExamAttempt extends ViewRecord
{
    protected static string $resource = StudentExamAttemptResource::class;

    protected function calculateTotalScore(): void
    {
        $this->record->load('answers.question');
        $totalScore = $this->record->answers->sum('points_awarded') ?? 0;

        $this->form->fill([
            'score' => $totalScore,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('exams.save_grades'))
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->record->status !== 'graded')
                ->action(function () {
                    $data = $this->form->getState();

                    $totalScore = 0;
                    $maxScore = 0;

                    if (isset($data['answers']) && is_array($data['answers'])) {
                        foreach ($data['answers'] as $answerData) {
                            if (isset($answerData['id'])) {
                                $answer = \App\Domain\Training\Models\ExamAnswer::find($answerData['id']);
                                if ($answer) {
                                    if (isset($answerData['points_awarded'])) {
                                        $answer->points_awarded = $answerData['points_awarded'] ?? 0;
                                    }
                                    if (isset($answerData['feedback'])) {
                                        $answer->feedback = $answerData['feedback'];
                                    }
                                    $answer->save();

                                    $question = $answer->question;
                                    if ($question) {
                                        $maxScore += $question->points ?? 0;
                                        $totalScore += $answer->points_awarded ?? 0;
                                    }
                                }
                            }
                        }
                    }

                    if (isset($data['score'])) {
                        $this->record->score = $data['score'];
                    } else {
                        $this->record->score = $totalScore;
                    }

                    $this->record->max_score = $maxScore;
                    if ($maxScore > 0) {
                        $this->record->percentage = ($this->record->score / $maxScore) * 100;
                    }

                    if (isset($data['status'])) {
                        $this->record->status = $data['status'];
                        if ($data['status'] === 'graded') {
                            $this->record->graded_at = now();
                            $this->record->graded_by_teacher_id = auth('teacher')->id();
                        }
                    }

                    $this->record->save();
                    $this->refreshFormData(['record']);
                }),
            Actions\Action::make('auto_grade_mcq')
                ->label(__('exams.auto_grade_mcq'))
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === 'submitted')
                ->requiresConfirmation()
                ->action(function () {
                    app(\App\Services\Student\ExamGradingService::class)->autoGradeMcq($this->record);
                    $this->refreshFormData(['record']);
                }),
            Actions\Action::make('finalize_grade')
                ->label(__('exams.finalize_grade'))
                ->icon('heroicon-o-flag')
                ->visible(fn () => $this->record->status === 'submitted')
                ->requiresConfirmation()
                ->action(function () {
                    $teacherId = auth('teacher')->id();
                    app(\App\Services\Student\ExamGradingService::class)->finalizeGrade($this->record, $teacherId);
                    $this->refreshFormData(['record']);
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attempt Information')
                    ->schema([
                        Forms\Components\TextInput::make('student_name')
                            ->label(__('exams.student'))
                            ->formatStateUsing(fn () => $this->record->student->name ?? '')
                            ->disabled(),
                        Forms\Components\TextInput::make('exam_title')
                            ->formatStateUsing(fn () => MultilingualHelper::formatMultilingualField($this->record->exam->title ?? []))
                            ->label(__('exams.exam'))
                            ->disabled(),
                        Forms\Components\TextInput::make('attempt_no')
                            ->label(__('exams.attempt_no'))
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'in_progress' => __('exams.status.in_progress'),
                                'submitted' => __('exams.status.submitted'),
                                'graded' => __('exams.status.graded'),
                            ])
                            ->label(__('exams.status'))
                            ->disabled(fn () => $this->record->status === 'graded')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($state === 'graded') {
                                    $this->record->graded_at = now();
                                    $this->record->graded_by_teacher_id = auth('teacher')->id();
                                }
                            }),
                        Forms\Components\TextInput::make('score')
                            ->label(__('exams.score'))
                            ->numeric()
                            ->disabled(fn () => $this->record->status === 'graded')
                            ->suffix(fn () => '/' . ($this->record->max_score ?? 0))
                            ->helperText(__('exams.score_auto_calculated'))
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($this->record->max_score > 0) {
                                    $this->record->percentage = ($state / $this->record->max_score) * 100;
                                }
                            }),
                        Forms\Components\TextInput::make('started_at')
                            ->label(__('exams.started_at'))
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '';
                                if (is_string($state)) return $state;
                                if (is_object($state) && method_exists($state, 'format')) {
                                    return $state->format('Y-m-d H:i:s');
                                }
                                return (string)$state;
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('submitted_at')
                            ->label(__('exams.submitted_at'))
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '';
                                if (is_string($state)) return $state;
                                if (is_object($state) && method_exists($state, 'format')) {
                                    return $state->format('Y-m-d H:i:s');
                                }
                                return (string)$state;
                            })
                            ->disabled(),
                    ]),
                Forms\Components\Section::make('Questions & Answers')
                    ->schema([
                        Forms\Components\Repeater::make('answers')
                            ->relationship('answers')
                            ->schema([
                                Forms\Components\Placeholder::make('question_display')
                                    ->label(__('exams.question'))
                                    ->content(function (Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['question'])) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-base font-medium text-gray-900 dark:text-gray-100 whitespace-pre-wrap">' .
                                                nl2br(e(MultilingualHelper::formatMultilingualField($question['question']))) .
                                                '</div>'
                                            );
                                        }
                                        return '';
                                    }),
                                Forms\Components\Placeholder::make('question_type_display')
                                    ->label(__('exams.type'))
                                    ->content(function (Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['type'])) {
                                            return __('exams.type_options.' . $question['type']);
                                        }
                                        return '';
                                    }),
                                Forms\Components\Placeholder::make('question_options_display')
                                    ->label(__('exams.options'))
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'mcq')
                                    ->content(function (Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['type']) && $question['type'] === 'mcq') {
                                            $options = $question['options'] ?? [];
                                            $correctIndex = $question['correct_answer'] ?? null;
                                            $html = '<div class="space-y-1">';
                                            foreach ($options as $index => $option) {
                                                $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                $isCorrect = $index === $correctIndex;
                                                $color = $isCorrect ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-700 dark:text-gray-300';
                                                $marker = $isCorrect ? '✓ ' : '  ';
                                                $html .= '<div class="' . $color . '">' . $marker . ($index + 1) . '. ' . e($text) . '</div>';
                                            }
                                            $html .= '</div>';
                                            return new \Illuminate\Support\HtmlString($html);
                                        }
                                        return '';
                                    }),
                                Forms\Components\Placeholder::make('correct_answer_display')
                                    ->label(__('exams.correct_answer'))
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'mcq')
                                    ->content(function (Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['type']) && $question['type'] === 'mcq') {
                                            $options = $question['options'] ?? [];
                                            $correctIndex = $question['correct_answer'] ?? null;
                                            if ($correctIndex !== null && isset($options[$correctIndex])) {
                                                $option = $options[$correctIndex];
                                                $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                return new \Illuminate\Support\HtmlString(
                                                    '<span class="text-green-600 dark:text-green-400 font-semibold">' .
                                                    ($correctIndex + 1) . '. ' . e($text) .
                                                    '</span>'
                                                );
                                            }
                                        }
                                        return '';
                                    }),
                                Forms\Components\Placeholder::make('student_answer_display')
                                    ->label(__('exams.student_answer'))
                                    ->content(function (Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        $selectedOption = $get('../../selected_option');
                                        $answerText = $get('../../answer_text');

                                        if ($question && isset($question['type'])) {
                                            if ($question['type'] === 'mcq' && $selectedOption !== null) {
                                                $options = $question['options'] ?? [];
                                                $selectedIndex = is_numeric($selectedOption) ? (int)$selectedOption : null;
                                                if ($selectedIndex !== null && isset($options[$selectedIndex])) {
                                                    $option = $options[$selectedIndex];
                                                    $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                    $isCorrect = $get('../../is_correct');
                                                    $color = $isCorrect ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="' . $color . ' font-medium">' .
                                                        ($selectedIndex + 1) . '. ' . e($text) .
                                                        ($isCorrect ? ' <span class="text-xs">(' . __('exams.correct') . ')</span>' : ' <span class="text-xs">(' . __('exams.incorrect') . ')</span>') .
                                                        '</div>'
                                                    );
                                                }
                                                return $selectedOption;
                                            } elseif ($question['type'] === 'essay' && $answerText) {
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="text-base text-gray-900 dark:text-gray-100 whitespace-pre-wrap bg-gray-50 dark:bg-gray-800 p-3 rounded border">' .
                                                    nl2br(e($answerText)) .
                                                    '</div>'
                                                );
                                            }
                                        }
                                        return __('exams.no_answer');
                                    }),
                                Forms\Components\TextInput::make('points_awarded')
                                    ->numeric()
                                    ->required(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'essay' && $this->record->status !== 'graded')
                                    ->disabled(fn () => $this->record->status === 'graded')
                                    ->visible(fn () => true)
                                    ->label(__('exams.points_awarded'))
                                    ->suffix(fn (Forms\Get $get) => '/' . ($get('../../question_data')['points'] ?? 0))
                                    ->maxValue(fn (Forms\Get $get) => ($get('../../question_data')['points'] ?? 0))
                                    ->reactive()
                                    ->afterStateUpdated(function () {
                                        $this->calculateTotalScore();
                                    }),
                                Forms\Components\Textarea::make('feedback')
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'essay')
                                    ->disabled(fn () => $this->record->status === 'graded')
                                    ->label(__('exams.feedback'))
                                    ->rows(3),
                            ])
                            ->disabled(fn () => $this->record->status === 'graded')
                            ->itemLabel(function (array $state) {
                                if (isset($state['question_data']['question'])) {
                                    $questionText = MultilingualHelper::formatMultilingualField($state['question_data']['question']);
                                    return mb_substr(strip_tags($questionText), 0, 50) . '...';
                                }
                                return 'Answer';
                            })
                            ->collapsible()
                            ->reorderable(false)
                            ->deletable(false)
                            ->addable(false)
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                $question = \App\Domain\Training\Models\ExamQuestion::find($data['question_id'] ?? null);
                                if ($question) {
                                    $data['question_data'] = [
                                        'type' => $question->type,
                                        'question' => $question->question,
                                        'options' => $question->options,
                                        'correct_answer' => $question->correct_answer,
                                        'points' => $question->points,
                                    ];
                                }
                                return $data;
                            }),
                    ]),
            ]);
    }
}
