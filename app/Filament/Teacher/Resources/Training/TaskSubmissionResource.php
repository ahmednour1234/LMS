<?php

namespace App\Filament\Teacher\Resources\Training;

use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use App\Filament\Teacher\Resources\Training\TaskSubmissionResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class TaskSubmissionResource extends Resource
{
    protected static ?string $model = TaskSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 10;

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

    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth('teacher')->id();
        return parent::getEloquentQuery()->whereHas('task.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();

        return $form->schema([
            Forms\Components\Select::make('task_id')
                ->label(__('task_submissions.task'))
                ->options(fn () => Task::query()
                    ->whereHas('course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
                    ->get(['id', 'title'])
                    ->mapWithKeys(fn ($task) => [
                        $task->id => (MultilingualHelper::formatMultilingualField($task->title) ?? 'N/A'),
                    ])
                    ->toArray()
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled()
                ->dehydrated(),

            Forms\Components\Select::make('student_id')
                ->relationship('student', 'student_code')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->student_code ?? 'N/A')
                ->searchable()
                ->preload()
                ->required()
                ->label(__('task_submissions.student'))
                ->disabled()
                ->dehydrated(),

            Forms\Components\Textarea::make('submission_text')
                ->label(__('task_submissions.submission_text'))
                ->rows(5)
                ->disabled()
                ->dehydrated(false),

            Forms\Components\Placeholder::make('media_file')
                ->label(__('task_submissions.media_file'))
                ->content(function ($record) {
                    if (!$record?->mediaFile) {
                        return __('task_submissions.no_file');
                    }

                    $mediaFile = $record->mediaFile;
                    $filename = $mediaFile->original_filename ?? $mediaFile->filename ?? 'View File';
                    $url = static::getUrl('view', ['record' => $record->id]);

                    return new \Illuminate\Support\HtmlString(
                        '<div class="flex items-center gap-2">
                            <span class="text-sm text-gray-700">' . htmlspecialchars($filename) . '</span>
                            <a href="' . $url . '" class="text-primary-600 hover:text-primary-700 underline text-sm">' .
                            __('task_submissions.view_file') .
                            '</a>
                        </div>'
                    );
                })
                ->visible(fn ($record) => $record?->mediaFile),

            Forms\Components\TextInput::make('score')
                ->numeric()
                ->label(__('task_submissions.score'))
                ->minValue(0)
                ->maxValue(fn ($record) => $record?->task?->max_score ?? 100),

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
                    ->label(__('task_submissions.status'))
                    ->color(fn ($state) => $state === 'reviewed' ? 'success' : 'warning'),

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
                    ->options(fn () => Task::query()
                        ->whereHas('course', fn ($q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                        ->get(['id', 'title'])
                        ->mapWithKeys(fn ($task) => [
                            $task->id => (MultilingualHelper::formatMultilingualField($task->title) ?? 'N/A'),
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
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTaskSubmissions::route('/'),
            'view'   => Pages\ViewTaskSubmission::route('/{record}'),
            'edit'   => Pages\EditTaskSubmission::route('/{record}/edit'),
        ];
    }
}
