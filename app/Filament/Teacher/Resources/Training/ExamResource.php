<?php

namespace App\Filament\Teacher\Resources\Training;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\Lesson;
use App\Filament\Teacher\Resources\Training\ExamResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('navigation.exams');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.exams');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.exams');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth('teacher')->id();
        return parent::getEloquentQuery()->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId));
    }

    public static function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();

        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => $query->where('owner_teacher_id', $teacherId))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('exams.course'))
                    ->live(),
                Forms\Components\Placeholder::make('course_info')
                    ->label(__('exams.course'))
                    ->content(function (Forms\Get $get) {
                        $courseId = $get('course_id');
                        if (!$courseId) {
                            return '-';
                        }
                        $course = Course::find($courseId);
                        if (!$course) {
                            return '-';
                        }
                        $name = MultilingualHelper::formatMultilingualField($course->name);
                        return $course->code . ' - ' . $name;
                    })
                    ->visible(fn (Forms\Get $get) => $get('course_id')),
                Forms\Components\Select::make('lesson_id')
                    ->relationship('lesson', 'id', function (Builder $query, Forms\Get $get) use ($teacherId) {
                        $courseId = $get('course_id');
                        if ($courseId) {
                            $query->whereHas('section', fn ($q) => $q->where('course_id', $courseId)->whereHas('course', fn ($c) => $c->where('owner_teacher_id', $teacherId)));
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
                    ->visible(fn ($get) => $get('course_id')),
                Forms\Components\Placeholder::make('lesson_info')
                    ->label(__('exams.lesson'))
                    ->content(function (Forms\Get $get) {
                        $lessonId = $get('lesson_id');
                        if (!$lessonId) {
                            return '-';
                        }
                        $lesson = Lesson::find($lessonId);
                        if (!$lesson) {
                            return '-';
                        }
                        return MultilingualHelper::formatMultilingualField($lesson->title) ?: '-';
                    })
                    ->visible(fn (Forms\Get $get) => $get('lesson_id')),
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
                    ->label(__('exams.type')),
                Forms\Components\TextInput::make('total_score')
                    ->numeric()
                    ->default(0)
                    ->label(__('exams.total_grade'))
                    ->dehydrated(false)
                    ->disabled()
                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                        if ($record) {
                            $totalPoints = $record->questions()->sum('points');
                            $component->state($totalPoints);
                        }
                    }),
                Forms\Components\TextInput::make('duration_minutes')
                    ->numeric()
                    ->label(__('exams.duration_minutes')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('exams.is_active'))
                    ->default(true),
                Forms\Components\Repeater::make('questions')
                    ->relationship('questions')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'mcq' => __('exams.type_options.mcq'),
                                'essay' => __('exams.type_options.essay'),
                            ])
                            ->required()
                            ->live()
                            ->label(__('exams.type')),
                        Forms\Components\Textarea::make('question.ar')
                            ->label(__('exams.question_ar'))
                            ->required()
                            ->rows(2),
                        Forms\Components\Textarea::make('question.en')
                            ->label(__('exams.question_en'))
                            ->required()
                            ->rows(2),
                        Forms\Components\TextInput::make('points')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->label(__('exams.points')),
                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->label(__('exams.order')),
                        Forms\Components\Repeater::make('options')
                            ->schema([
                                Forms\Components\TextInput::make('option')
                                    ->required()
                                    ->label(__('exams.option'))
                                    ->live(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('type') === 'mcq')
                            ->required(fn (Forms\Get $get) => $get('type') === 'mcq')
                            ->label(__('exams.options'))
                            ->live(),
                        Forms\Components\Select::make('correct_answer')
                            ->options(function (Forms\Get $get) {
                                $options = $get('options') ?? [];
                                if (empty($options) || !is_array($options)) {
                                    return [];
                                }
                                return collect($options)->mapWithKeys(function ($option, $index) {
                                    $optionValue = is_array($option) ? ($option['option'] ?? '') : (string) $option;
                                    if (empty($optionValue)) {
                                        return [];
                                    }
                                    return [$index => $optionValue];
                                })->filter()->toArray();
                            })
                            ->visible(fn (Forms\Get $get) => $get('type') === 'mcq')
                            ->required(fn (Forms\Get $get) => $get('type') === 'mcq')
                            ->live()
                            ->label(__('exams.correct_answer')),
                    ])
                    ->collapsible()
                    ->itemLabel(fn (Forms\Get $get) => 'Q' . ($get('order') ?? 0) . ': ' . MultilingualHelper::formatMultilingualField($get('question') ?? []))
                    ->label(__('exams.questions'))
                    ->orderColumn('order'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['course', 'lesson']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->sortable()
                    ->label(__('exams.course')),
                Tables\Columns\TextColumn::make('lesson.title')
                    ->formatStateUsing(fn ($state, $record) => $state ? MultilingualHelper::formatMultilingualField($state) : ($record && $record->lesson ? MultilingualHelper::formatMultilingualField($record->lesson->title) : '-'))
                    ->sortable()
                    ->label(__('exams.lesson')),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state, $record) => $state ? MultilingualHelper::formatMultilingualField($state) : ($record && $record->title ? MultilingualHelper::formatMultilingualField($record->title) : ''))
                    ->searchable()
                    ->sortable()
                    ->label(__('exams.title')),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('exams.type_options.' . $state))
                    ->label(__('exams.type')),
                Tables\Columns\TextColumn::make('total_score')
                    ->sortable()
                    ->label(__('exams.total_score')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('exams.is_active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => $query->where('owner_teacher_id', auth('teacher')->id()))
                    ->label(__('exams.course')),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'mcq' => __('exams.type_options.mcq'),
                        'essay' => __('exams.type_options.essay'),
                        'mixed' => __('exams.type_options.mixed'),
                    ])
                    ->label(__('exams.type')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('exams.is_active')),
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
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'view' => Pages\ViewExam::route('/{record}'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
