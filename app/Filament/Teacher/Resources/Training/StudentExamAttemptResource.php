<?php

namespace App\Filament\Teacher\Resources\Training;

use App\Domain\Training\Models\ExamAttempt;
use App\Filament\Teacher\Resources\Training\StudentExamAttemptResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentExamAttemptResource extends Resource
{
    protected static ?string $model = ExamAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('exams.attempts');
    }

    public static function getModelLabel(): string
    {
        return __('exams.attempt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('exams.attempts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth('teacher')->id();
        return parent::getEloquentQuery()
            ->whereHas('exam.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
            ->with(['student', 'exam.course', 'answers.question']);
    }

    public static function form(Form $form): Form
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('exams.student')),
                Tables\Columns\TextColumn::make('exam.title')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->searchable()
                    ->sortable()
                    ->label(__('exams.exam')),
                Tables\Columns\TextColumn::make('attempt_no')
                    ->label(__('exams.attempt_no'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('exams.status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'in_progress' => 'warning',
                        'submitted' => 'info',
                        'graded' => 'success',
                        default => 'gray',
                    })
                    ->label(__('exams.status')),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exams.started_at')),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('exams.submitted_at')),
                Tables\Columns\TextColumn::make('score')
                    ->sortable()
                    ->label(__('exams.score')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_progress' => __('exams.status.in_progress'),
                        'submitted' => __('exams.status.submitted'),
                        'graded' => __('exams.status.graded'),
                    ])
                    ->label(__('exams.status')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('auto_grade_mcq')
                    ->label(__('exams.auto_grade_mcq'))
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (ExamAttempt $record) => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(function (ExamAttempt $record) {
                        app(\App\Services\Student\ExamGradingService::class)->autoGradeMcq($record);
                        $record->refresh();
                    }),
                Tables\Actions\Action::make('finalize_grade')
                    ->label(__('exams.finalize_grade'))
                    ->icon('heroicon-o-flag')
                    ->visible(fn (ExamAttempt $record) => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(function (ExamAttempt $record) {
                        $teacherId = auth('teacher')->id();
                        app(\App\Services\Student\ExamGradingService::class)->finalizeGrade($record, $teacherId);
                        $record->refresh();
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentExamAttempts::route('/'),
            'view' => Pages\ViewStudentExamAttempt::route('/{record}'),
        ];
    }
}