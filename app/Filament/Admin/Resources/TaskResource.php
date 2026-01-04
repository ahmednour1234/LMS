<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Task;
use App\Filament\Admin\Resources\TaskResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('navigation.tasks');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.tasks');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.tasks');
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
                    ->label(__('tasks.course'))
                    ->reactive(),
                Forms\Components\Select::make('lesson_id')
                    ->relationship('lesson', 'id', function (Builder $query, $get) {
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
                    ->label(__('tasks.lesson'))
                    ->visible(fn ($get) => $get('course_id')),
                Forms\Components\TextInput::make('title.ar')
                    ->label(__('tasks.title_ar'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title.en')
                    ->label(__('tasks.title_en'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description.ar')
                    ->label(__('tasks.description_ar'))
                    ->rows(3),
                Forms\Components\Textarea::make('description.en')
                    ->label(__('tasks.description_en'))
                    ->rows(3),
                Forms\Components\Select::make('submission_type')
                    ->options([
                        'file' => __('tasks.submission_type_options.file'),
                        'text' => __('tasks.submission_type_options.text'),
                    ])
                    ->required()
                    ->label(__('tasks.submission_type')),
                Forms\Components\TextInput::make('max_score')
                    ->numeric()
                    ->default(0)
                    ->label(__('tasks.max_score')),
                Forms\Components\DateTimePicker::make('due_date')
                    ->label(__('tasks.due_date')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('tasks.is_active'))
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
                    ->label(__('tasks.course')),
                Tables\Columns\TextColumn::make('lesson.title')
                    ->formatStateUsing(fn ($state) => $state ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : '-')
                    ->sortable()
                    ->label(__('tasks.lesson')),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->searchable()
                    ->sortable()
                    ->label(__('tasks.title')),
                Tables\Columns\TextColumn::make('submission_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('tasks.submission_type_options.' . $state))
                    ->label(__('tasks.submission_type')),
                Tables\Columns\TextColumn::make('max_score')
                    ->sortable()
                    ->label(__('tasks.max_score')),
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->label(__('tasks.due_date')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('tasks.is_active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'code')
                    ->label(__('tasks.course')),
                Tables\Filters\SelectFilter::make('submission_type')
                    ->options([
                        'file' => __('tasks.submission_type_options.file'),
                        'text' => __('tasks.submission_type_options.text'),
                    ])
                    ->label(__('tasks.submission_type')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('tasks.is_active')),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
