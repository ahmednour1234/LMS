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
                        Forms\Components\TextInput::make('student.name')
                            ->label(__('exams.student'))
                            ->disabled(),
                        Forms\Components\TextInput::make('exam.title')
                            ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
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
                            ->disabled()
                            ->label(__('exams.status')),
                        Forms\Components\TextInput::make('score')
                            ->label(__('exams.score'))
                            ->disabled(),
                        Forms\Components\TextInput::make('started_at')
                            ->label(__('exams.started_at'))
                            ->disabled(),
                        Forms\Components\TextInput::make('submitted_at')
                            ->label(__('exams.submitted_at'))
                            ->disabled(),
                    ]),
                Forms\Components\Section::make('Questions & Answers')
                    ->schema([
                        Forms\Components\Repeater::make('answers')
                            ->relationship('answers')
                            ->schema([
                                Forms\Components\Textarea::make('question.question')
                                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                                    ->disabled()
                                    ->label(__('exams.question')),
                                Forms\Components\Select::make('question.type')
                                    ->options([
                                        'mcq' => __('exams.type_options.mcq'),
                                        'essay' => __('exams.type_options.essay'),
                                    ])
                                    ->disabled()
                                    ->label(__('exams.type')),
                                Forms\Components\Textarea::make('question.options')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        if ($get('../../question.type') !== 'mcq' || !$state) {
                                            return null;
                                        }
                                        $options = is_array($state) ? $state : [];
                                        $correctIndex = $get('../../question.correct_answer');
                                        $formatted = [];
                                        foreach ($options as $index => $option) {
                                            $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                            $isCorrect = $index === $correctIndex;
                                            $formatted[] = ($isCorrect ? '✓ ' : '  ') . ($index + 1) . '. ' . $text;
                                        }
                                        return implode("\n", $formatted);
                                    })
                                    ->visible(fn (Forms\Get $get) => $get('../../question.type') === 'mcq')
                                    ->disabled()
                                    ->label(__('exams.options'))
                                    ->rows(4),
                                Forms\Components\TextInput::make('question.correct_answer')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        if ($get('../../question.type') !== 'mcq' || $state === null) {
                                            return null;
                                        }
                                        $options = $get('../../question.options') ?? [];
                                        if (isset($options[$state])) {
                                            $option = $options[$state];
                                            $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                            return ($state + 1) . '. ' . $text;
                                        }
                                        return __('exams.option') . ' ' . ($state + 1);
                                    })
                                    ->visible(fn (Forms\Get $get) => $get('../../question.type') === 'mcq')
                                    ->disabled()
                                    ->label(__('exams.correct_answer')),
                                Forms\Components\Textarea::make('answer_text')
                                    ->visible(fn (Forms\Get $get) => $get('../../question.type') === 'essay')
                                    ->disabled()
                                    ->label(__('exams.answer_text')),
                                Forms\Components\TextInput::make('selected_option')
                                    ->formatStateUsing(function ($state, Forms\Get $get) {
                                        if (!$state) return null;
                                        $options = $get('../../question.options') ?? [];
                                        $selectedIndex = is_numeric($state) ? (int)$state : null;
                                        if ($selectedIndex !== null && isset($options[$selectedIndex])) {
                                            $option = $options[$selectedIndex];
                                            $text = is_array($option) ? ($option['text'] ?? '') : $option;
                                            return ($selectedIndex + 1) . '. ' . $text;
                                        }
                                        return $state;
                                    })
                                    ->visible(fn (Forms\Get $get) => $get('../../question.type') === 'mcq')
                                    ->disabled()
                                    ->label(__('exams.selected_option')),
                                Forms\Components\TextInput::make('is_correct')
                                    ->formatStateUsing(fn ($state) => $state ? __('exams.correct') : __('exams.incorrect'))
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => $get('../../question.type') === 'mcq')
                                    ->label(__('exams.is_correct')),
                                Forms\Components\TextInput::make('points_awarded')
                                    ->numeric()
                                    ->required(fn (Forms\Get $get) => $get('../../question.type') === 'essay' && $this->record->status !== 'graded')
                                    ->disabled(fn (Forms\Get $get) => $get('../../question.type') === 'mcq' || $this->record->status === 'graded')
                                    ->visible(fn (Forms\Get $get) => true)
                                    ->label(__('exams.points_awarded'))
                                    ->maxValue(fn (Forms\Get $get) => $get('../../question.points') ?? 0),
                                Forms\Components\Textarea::make('feedback')
                                    ->visible(fn (Forms\Get $get) => $get('../../question.type') === 'essay')
                                    ->disabled(fn () => $this->record->status === 'graded')
                                    ->label(__('exams.feedback')),
                            ])
                            ->disabled(fn () => $this->record->status === 'graded')
                            ->itemLabel(fn (Forms\Get $get) => MultilingualHelper::formatMultilingualField($get('question.question') ?? []))
                            ->collapsible()
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                $question = \App\Domain\Training\Models\ExamQuestion::find($data['question_id'] ?? null);
                                if ($question) {
                                    $data['question'] = [
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