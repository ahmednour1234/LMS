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
                                Forms\Components\Textarea::make('answer_text')
                                    ->visible(fn (Forms\Get $get) => $get('../../question.type') === 'essay')
                                    ->disabled()
                                    ->label(__('exams.answer_text')),
                                Forms\Components\TextInput::make('selected_option')
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
                                    ->label(__('exams.feedback')),
                            ])
                            ->disabled(fn () => $this->record->status === 'graded')
                            ->itemLabel(fn (Forms\Get $get) => MultilingualHelper::formatMultilingualField($get('question.question') ?? []))
                            ->collapsible(),
                    ]),
            ]);
    }
}