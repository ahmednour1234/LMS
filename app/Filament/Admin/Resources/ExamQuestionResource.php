<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamQuestion;
use App\Filament\Admin\Resources\ExamQuestionResource\Pages;
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

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 6;

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
                    ->relationship('exam', 'id', fn (Builder $query) => $query->orderBy('id'))
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
                    ->reactive(),
                Forms\Components\Select::make('type')
                    ->options([
                        'mcq' => __('exam_questions.type_options.mcq'),
                        'essay' => __('exam_questions.type_options.essay'),
                    ])
                    ->required()
                    ->label(__('exam_questions.type'))
                    ->reactive(),
                Forms\Components\Radio::make('question_lang')
                    ->label(__('exam_questions.question_language'))
                    ->options([
                        'ar' => __('general.arabic'),
                        'en' => __('general.english'),
                    ])
                    ->default('en')
                    ->live()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->question) {
                            $question = is_array($record->question) ? $record->question : [];
                            if (!empty($question['ar']) && empty($question['en'])) {
                                $component->state('ar');
                            } elseif (!empty($question['en']) && empty($question['ar'])) {
                                $component->state('en');
                            } elseif (!empty($question['ar'])) {
                                $component->state('ar');
                            } else {
                                $component->state('en');
                            }
                        }
                    })
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
                Forms\Components\Repeater::make('options')
                    ->schema([
                        Forms\Components\TextInput::make('ar')
                            ->label(__('exam_questions.option_ar'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('en')
                            ->label(__('exam_questions.option_en'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->label(__('exam_questions.options'))
                    ->visible(fn ($get) => $get('type') === 'mcq'),
                Forms\Components\Select::make('correct_answer')
                    ->label(__('exam_questions.correct_answer'))
                    ->options(function ($get, $record) {
                        $options = $get('options') ?? [];
                        if ($record && $record->options) {
                            $options = is_array($record->options) ? $record->options : [];
                        }
                        $opts = [];
                        foreach ($options as $index => $option) {
                            if (is_array($option)) {
                                $text = $option['en'] ?? $option['ar'] ?? "Option " . ($index + 1);
                            } elseif (is_string($option)) {
                                $text = $option;
                            } else {
                                $text = "Option " . ($index + 1);
                            }
                            $opts[$index] = $text;
                        }
                        return $opts;
                    })
                    ->visible(fn ($get) => $get('type') === 'mcq')
                    ->reactive(),
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
