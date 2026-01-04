<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\Lesson;
use App\Filament\Admin\Resources\ExamResource\Pages;
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

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 6;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => $query->where('branch_id', auth()->user()->branch_id ?? null))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('exams.course'))
                    ->reactive(),
                Forms\Components\Select::make('lesson_id')
                    ->relationship('lesson', null, function (Builder $query, $get) {
                        $courseId = $get('course_id');
                        if ($courseId) {
                            $query->whereHas('section', fn ($q) => $q->where('course_id', $courseId));
                        }
                        return $query->orderBy('id');
                    })
                    ->getOptionLabelUsing(fn ($record): ?string => is_object($record) ? ($record->title[app()->getLocale()] ?? $record->title['en'] ?? null) : (\App\Domain\Training\Models\Lesson::find($record)?->title[app()->getLocale()] ?? \App\Domain\Training\Models\Lesson::find($record)?->title['en'] ?? null))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('exams.lesson'))
                    ->visible(fn ($get) => $get('course_id')),
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
                    ->label(__('exams.total_score')),
                Forms\Components\TextInput::make('duration_minutes')
                    ->numeric()
                    ->label(__('exams.duration_minutes')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('exams.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->sortable()
                    ->label(__('exams.course')),
                Tables\Columns\TextColumn::make('lesson.title')
                    ->formatStateUsing(fn ($state) => $state ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : '-')
                    ->sortable()
                    ->label(__('exams.lesson')),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
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
                    ->relationship('course', 'code')
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
