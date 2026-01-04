<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamQuestion;
use App\Filament\Admin\Resources\ExamQuestionResource\Pages;
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

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exam_id')
                    ->relationship('exam', null, fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? null))->orderBy('id'))
                    ->getOptionLabelUsing(fn ($record): ?string => is_object($record) ? ($record->title[app()->getLocale()] ?? $record->title['en'] ?? null) : (\App\Domain\Training\Models\Exam::find($record)?->title[app()->getLocale()] ?? \App\Domain\Training\Models\Exam::find($record)?->title['en'] ?? null))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('exam_questions.exam'))
                    ->reactive(),
                Forms\Components\Select::make('type')
                    ->options([
                        'mcq' => __('exam_questions.type_options.mcq'),
                        'essay' => __('exam_questions.type_options.essay'),
                    ])
                    ->required()
                    ->label(__('exam_questions.type'))
                    ->reactive(),
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
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('exam.course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('exam.title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->sortable()
                    ->label(__('exam_questions.exam')),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('exam_questions.type_options.' . $state))
                    ->label(__('exam_questions.type')),
                Tables\Columns\TextColumn::make('question')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
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
                    ->relationship('exam', 'title')
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
