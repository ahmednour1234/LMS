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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('exams.save_grades'))
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->record->status === 'submitted')
                ->action(function () {
                    $data = $this->form->getState();
                    if (isset($data['answers']) && is_array($data['answers'])) {
                        foreach ($data['answers'] as $answerData) {
                            if (isset($answerData['id'])) {
                                $answer = \App\Domain\Training\Models\ExamAnswer::find($answerData['id']);
                                if ($answer) {
                                    if (isset($answerData['points_awarded'])) {
                                        $answer->points_awarded = $answerData['points_awarded'];
                                    }
                                    if (isset($answerData['feedback'])) {
                                        $answer->feedback = $answerData['feedback'];
                                    }
                                    $answer->save();
                                }
                            }
                        }
                    }
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
                        Forms\Components\TextInput::make('status_display')
                            ->formatStateUsing(fn () => __('exams.status.' . ($this->record->status ?? 'in_progress')))
                            ->label(__('exams.status'))
                            ->disabled(),
                        Forms\Components\TextInput::make('score')
                            ->label(__('exams.score'))
                            ->formatStateUsing(fn ($state) => $state ?? '0')
                            ->disabled(),
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
                                Forms\Components\Textarea::make('question_text')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['question'])) {
                                            return MultilingualHelper::formatMultilingualField($question['question']);
                                        }
                                        return '';
                                    })
                                    ->disabled()
                                    ->label(__('exams.question')),
                                Forms\Components\TextInput::make('question_type')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['type'])) {
                                            return __('exams.type_options.' . $question['type']);
                                        }
                                        return '';
                                    })
                                    ->disabled()
                                    ->label(__('exams.type')),
                                Forms\Components\Textarea::make('question_options')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['type']) && $question['type'] === 'mcq') {
                                            $options = $question['options'] ?? [];
                                            $correctIndex = $question['correct_answer'] ?? null;
                                            $formatted = [];
                                            foreach ($options as $index => $option) {
                                                $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                $isCorrect = $index === $correctIndex;
                                                $formatted[] = ($isCorrect ? '✓ ' : '  ') . ($index + 1) . '. ' . $text;
                                            }
                                            return implode("\n", $formatted);
                                        }
                                        return null;
                                    })
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'mcq')
                                    ->disabled()
                                    ->label(__('exams.options'))
                                    ->rows(4),
                                Forms\Components\TextInput::make('correct_answer_display')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['type']) && $question['type'] === 'mcq') {
                                            $options = $question['options'] ?? [];
                                            $correctIndex = $question['correct_answer'] ?? null;
                                            if ($correctIndex !== null && isset($options[$correctIndex])) {
                                                $option = $options[$correctIndex];
                                                $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                return ($correctIndex + 1) . '. ' . $text;
                                            }
                                            return __('exams.option') . ' ' . (($correctIndex ?? 0) + 1);
                                        }
                                        return null;
                                    })
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'mcq')
                                    ->disabled()
                                    ->label(__('exams.correct_answer')),
                                Forms\Components\Textarea::make('answer_text')
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'essay')
                                    ->disabled()
                                    ->label(__('exams.answer_text')),
                                Forms\Components\TextInput::make('selected_option_display')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        $selectedOption = $get('../../selected_option');
                                        if (!$selectedOption) return null;
                                        $question = $get('../../question_data') ?? null;
                                        if ($question && isset($question['type']) && $question['type'] === 'mcq') {
                                            $options = $question['options'] ?? [];
                                            $selectedIndex = is_numeric($selectedOption) ? (int)$selectedOption : null;
                                            if ($selectedIndex !== null && isset($options[$selectedIndex])) {
                                                $option = $options[$selectedIndex];
                                                $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                                return ($selectedIndex + 1) . '. ' . $text;
                                            }
                                        }
                                        return $selectedOption;
                                    })
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'mcq')
                                    ->disabled()
                                    ->label(__('exams.selected_option')),
                                Forms\Components\TextInput::make('is_correct')
                                    ->formatStateUsing(fn ($state) => $state ? __('exams.correct') : __('exams.incorrect'))
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'mcq')
                                    ->label(__('exams.is_correct')),
                                Forms\Components\TextInput::make('points_awarded')
                                    ->numeric()
                                    ->required(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'essay' && $this->record->status !== 'graded')
                                    ->disabled(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'mcq' || $this->record->status === 'graded')
                                    ->visible(fn () => true)
                                    ->label(__('exams.points_awarded'))
                                    ->maxValue(fn (Forms\Get $get) => ($get('../../question_data')['points'] ?? 0)),
                                Forms\Components\Textarea::make('feedback')
                                    ->visible(fn (Forms\Get $get) => ($get('../../question_data')['type'] ?? '') === 'essay')
                                    ->disabled(fn () => $this->record->status === 'graded')
                                    ->label(__('exams.feedback')),
                            ])
                            ->disabled(fn () => $this->record->status === 'graded')
                            ->itemLabel(function (array $state) {
                                if (isset($state['question_data']['question'])) {
                                    return MultilingualHelper::formatMultilingualField($state['question_data']['question']);
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
