<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Task;
use App\Filament\Admin\Resources\TaskResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Build Courses query filtered by current user's branch if possible.
     */
    protected static function coursesQueryForUserBranch(): Builder
    {
        $query = \App\Domain\Training\Models\Course::query()->orderBy('code');

        $branchId = Auth::user()?->branch_id;

        // ✅ Safe filter: only if courses.branch_id exists
        if ($branchId && Schema::hasColumn('courses', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        /**
         * لو عندك الفلترة الحقيقية عبر برامج/جدول وسيط:
         * عدّل هنا بدل ما تعمل where على programs.branch_id (اللي مش موجود).
         *
         * مثال لو فيه pivot program_branch(program_id, branch_id):
         * $query->whereHas('program.branches', fn($q) => $q->where('branches.id', $branchId));
         */

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('course_id')
                ->label(__('tasks.course'))
                ->options(fn () => self::coursesQueryForUserBranch()->pluck('code', 'id')->toArray())
                ->searchable()
                ->preload()
                ->required()
                ->live() // بدل reactive في Filament 3
                ->afterStateUpdated(fn (Forms\Set $set) => $set('lesson_id', null)),

            Forms\Components\Select::make('lesson_id')
                ->label(__('tasks.lesson'))
                ->options(function (Forms\Get $get) {
                    $courseId = $get('course_id');
                    if (!$courseId) {
                        return [];
                    }

                    // lessons مرتبطة بـ section -> course
                    return Lesson::query()
                        ->whereHas('section', fn (Builder $q) => $q->where('course_id', $courseId))
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(function ($lesson) {
                            $label = MultilingualHelper::formatMultilingualField($lesson->title) ?: 'N/A';
                            return [$lesson->id => $label];
                        })
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn (Forms\Get $get) => (bool) $get('course_id')),

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
                ->label(__('tasks.submission_type'))
                ->options([
                    'file' => __('tasks.submission_type_options.file'),
                    'text' => __('tasks.submission_type_options.text'),
                ])
                ->required(),

            Forms\Components\TextInput::make('max_score')
                ->label(__('tasks.max_score'))
                ->numeric()
                ->default(0)
                ->minValue(0),

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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['course', 'lesson']))
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->sortable()
                    ->label(__('tasks.course')),

                Tables\Columns\TextColumn::make('lesson.title')
                    ->label(__('tasks.lesson'))
                    ->formatStateUsing(fn ($state, $record) =>
                        $record?->lesson?->title
                            ? MultilingualHelper::formatMultilingualField($record->lesson->title)
                            : '-'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('tasks.title'))
                    ->formatStateUsing(fn ($state, $record) =>
                        $record?->title ? MultilingualHelper::formatMultilingualField($record->title) : ''
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('submission_type')
                    ->label(__('tasks.submission_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('tasks.submission_type_options.' . $state)),

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
                    ->label(__('tasks.course'))
                    ->options(fn () => self::coursesQueryForUserBranch()->pluck('code', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('submission_type')
                    ->label(__('tasks.submission_type'))
                    ->options([
                        'file' => __('tasks.submission_type_options.file'),
                        'text' => __('tasks.submission_type_options.text'),
                    ]),

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
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view'   => Pages\ViewTask::route('/{record}'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
