<?php

namespace App\Filament\Teacher\Resources\Training;

use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamQuestion;
use App\Filament\Teacher\Resources\Training\ExamQuestionResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamQuestionResource extends Resource
{
    protected static ?string $model = ExamQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('navigation.exam_questions');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.exam_questions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.exam_questions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth('teacher')->id();
        return parent::getEloquentQuery()->whereHas('exam.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId));
    }

    public static function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();
        
        return $form
            ->schema([
                Forms\Components\Select::make('exam_id')
                    ->relationship('exam', 'id', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('owner_teacher_id', $teacherId))->orderBy('id'))
                    ->getOptionLabelUsing(function ($record): string {
                        if (is_object($record)) {
                            return MultilingualHelper::formatMultilingualField($record->title) ?: 'N/A';
                        }
                        $exam = Exam::find($record);
                        return $exam ? (MultilingualHelper::formatMultilingualField($exam->title) ?: 'N/A') : 'N/A';
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('exam_questions.exam'))
                    ->live(),
                Forms\Components\Select::make('type')
                    ->options([
                        'mcq' => __('exam_questions.type_options.mcq'),
                        'essay' => __('exam_questions.type_options.essay'),
                    ])
                    ->required()
                    ->label(__('exam_questions.type'))
                    ->live(),
                Forms\Components\Textarea::make('question.ar')
                    ->label(__('exam_questions.question_ar'))
                    ->required()
                    ->rows(3),
                Forms\Components\Textarea::make('question.en')
                    ->label(__('exam_questions.question_en'))
                    ->required()
                    ->rows(3),
                Forms\Components\Repeater::make('options')
                    ->schema([
                        Forms\Components\TextInput::make('option')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->label(__('exam_questions.options'))
                    ->visible(fn ($get) => $get('type') === 'mcq'),
                Forms\Components\TextInput::make('correct_answer')
                    ->label(__('exam_questions.correct_answer'))
                    ->visible(fn ($get) => $get('type') === 'mcq'),
                Forms\Components\TextInput::make('points')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->label(__('exam_questions.points')),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->label(__('exam_questions.order')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('exam');
            })
            ->columns([
                Tables\Columns\TextColumn::make('exam.title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : ($state ?? ''))
                    ->sortable()
                    ->label(__('exam_questions.exam')),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('exam_questions.type_options.' . $state))
                    ->label(__('exam_questions.type')),
                Tables\Columns\TextColumn::make('question')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : ($state ?? ''))
                    ->searchable()
                    ->label(__('exam_questions.question'))
                    ->limit(50),
                Tables\Columns\TextColumn::make('points')
                    ->sortable()
                    ->label(__('exam_questions.points')),
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label(__('exam_questions.order')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_id')
                    ->relationship('exam', 'title', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('owner_teacher_id', auth('teacher')->id())))
                    ->getOptionLabelUsing(function ($record): string {
                        if (is_object($record)) {
                            return MultilingualHelper::formatMultilingualField($record->title) ?: 'N/A';
                        }
                        $exam = Exam::find($record);
                        return $exam ? (MultilingualHelper::formatMultilingualField($exam->title) ?: 'N/A') : 'N/A';
                    })
                    ->label(__('exam_questions.exam')),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'mcq' => __('exam_questions.type_options.mcq'),
                        'essay' => __('exam_questions.type_options.essay'),
                    ])
                    ->label(__('exam_questions.type')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamQuestions::route('/'),
            'create' => Pages\CreateExamQuestion::route('/create'),
            'view' => Pages\ViewExamQuestion::route('/{record}'),
            'edit' => Pages\EditExamQuestion::route('/{record}/edit'),
        ];
    }
}
