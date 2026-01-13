<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use App\Filament\Admin\Resources\TaskSubmissionResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TaskSubmissionResource extends Resource
{
    protected static ?string $model = TaskSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';
    protected static ?string $navigationGroup = 'training';
    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('navigation.task_submissions');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.task_submissions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.task_submissions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    /**
     * ✅ Safe query: Filter tasks by user branch ONLY if we can.
     * Priority:
     * 1) tasks.branch_id (if exists)
     * 2) tasks -> course.branch_id (if exists)
     */
    protected static function tasksQueryForUserBranch(): Builder
    {
        $query = Task::query()->orderBy('id');

        $user = Auth::user();
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        $branchId = $user->branch_id;

        // 1) tasks.branch_id
        if (Schema::hasColumn('tasks', 'branch_id')) {
            return $query->where('branch_id', $branchId);
        }

        // 2) courses.branch_id through task->course
        if (Schema::hasColumn('courses', 'branch_id')) {
            return $query->whereHas('course', fn (Builder $q) => $q->where('branch_id', $branchId));
        }

        // Otherwise: no branch filter to avoid SQL errors.
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('task_submission_type')
                ->dehydrated(false),

            Forms\Components\Select::make('task_id')
                ->label(__('task_submissions.task'))
                ->options(fn () => self::tasksQueryForUserBranch()
                    ->get(['id', 'title'])
                    ->mapWithKeys(fn ($task) => [
                        $task->id => (MultilingualHelper::formatMultilingualField($task->title) ?: 'N/A'),
                    ])
                    ->toArray()
                )
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    $set('submission_text', null);
                    $set('media_file_id', null);

                    $type = Task::query()->whereKey($state)->value('submission_type');
                    $set('task_submission_type', $type);
                }),

            Forms\Components\Select::make('student_id')
                ->relationship('student', 'student_code')
                ->searchable()
                ->preload()
                ->required()
                ->label(__('task_submissions.student')),

            Forms\Components\Textarea::make('submission_text')
                ->label(__('task_submissions.submission_text'))
                ->rows(5)
                ->visible(fn (Forms\Get $get) => $get('task_submission_type') === 'text'),

            Forms\Components\Select::make('media_file_id')
                ->relationship('mediaFile', 'original_filename')
                ->searchable()
                ->preload()
                ->label(__('task_submissions.media_file'))
                ->visible(fn (Forms\Get $get) => $get('task_submission_type') === 'file'),

            Forms\Components\TextInput::make('score')
                ->numeric()
                ->label(__('task_submissions.score')),

            Forms\Components\Textarea::make('feedback.ar')
                ->label(__('task_submissions.feedback_ar'))
                ->rows(3),

            Forms\Components\Textarea::make('feedback.en')
                ->label(__('task_submissions.feedback_en'))
                ->rows(3),

            Forms\Components\Select::make('status')
                ->options([
                    'pending'  => __('task_submissions.status_options.pending'),
                    'reviewed' => __('task_submissions.status_options.reviewed'),
                ])
                ->required()
                ->label(__('task_submissions.status')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['task', 'student']);

                $user = Auth::user();
                if (!$user || $user->isSuperAdmin()) {
                    return $query;
                }

                $branchId = $user->branch_id;

                // ✅ Safe branch filter (same logic as above)
                if (Schema::hasColumn('tasks', 'branch_id')) {
                    return $query->whereHas('task', fn (Builder $q) => $q->where('branch_id', $branchId));
                }

                if (Schema::hasColumn('courses', 'branch_id')) {
                    return $query->whereHas('task.course', fn (Builder $q) => $q->where('branch_id', $branchId));
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label(__('task_submissions.task'))
                    ->formatStateUsing(fn ($state, $record) =>
                        $record?->task?->title
                            ? MultilingualHelper::formatMultilingualField($record->task->title)
                            : ''
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.student_code')
                    ->sortable()
                    ->label(__('task_submissions.student')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('task_submissions.status_options.' . $state))
                    ->label(__('task_submissions.status')),

                Tables\Columns\TextColumn::make('score')
                    ->sortable()
                    ->label(__('task_submissions.score')),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('task_submissions.reviewed_at')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('task_id')
                    ->label(__('task_submissions.task'))
                    ->options(fn () => self::tasksQueryForUserBranch()
                        ->get(['id', 'title'])
                        ->mapWithKeys(fn ($task) => [
                            $task->id => (MultilingualHelper::formatMultilingualField($task->title) ?: 'N/A'),
                        ])
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => __('task_submissions.status_options.pending'),
                        'reviewed' => __('task_submissions.status_options.reviewed'),
                    ])
                    ->label(__('task_submissions.status')),
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
            'index'  => Pages\ListTaskSubmissions::route('/'),
            'create' => Pages\CreateTaskSubmission::route('/create'),
            'view'   => Pages\ViewTaskSubmission::route('/{record}'),
            'edit'   => Pages\EditTaskSubmission::route('/{record}/edit'),
        ];
    }
}
