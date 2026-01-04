<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use App\Filament\Admin\Resources\TaskSubmissionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('task_id')
                    ->relationship('task', null, fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? null))->orderBy('id'))
                    ->getOptionLabelUsing(fn ($record): ?string => is_object($record) ? ($record->title[app()->getLocale()] ?? $record->title['en'] ?? null) : (\App\Domain\Training\Models\Task::find($record)?->title[app()->getLocale()] ?? \App\Domain\Training\Models\Task::find($record)?->title['en'] ?? null))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('task_submissions.task')),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'student_code')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('task_submissions.student')),
                Forms\Components\Textarea::make('submission_text')
                    ->label(__('task_submissions.submission_text'))
                    ->rows(5)
                    ->visible(fn ($get) => $get('task_id') && optional(Task::find($get('task_id')))->submission_type === 'text'),
                Forms\Components\Select::make('media_file_id')
                    ->relationship('mediaFile', 'original_filename')
                    ->searchable()
                    ->preload()
                    ->label(__('task_submissions.media_file'))
                    ->visible(fn ($get) => $get('task_id') && optional(Task::find($get('task_id')))->submission_type === 'file'),
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
                        'pending' => __('task_submissions.status_options.pending'),
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
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('task.course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->sortable()
                    ->label(__('task_submissions.task')),
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
                    ->relationship('task', 'title')
                    ->label(__('task_submissions.task')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('task_submissions.status_options.pending'),
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
            'index' => Pages\ListTaskSubmissions::route('/'),
            'create' => Pages\CreateTaskSubmission::route('/create'),
            'view' => Pages\ViewTaskSubmission::route('/{record}'),
            'edit' => Pages\EditTaskSubmission::route('/{record}/edit'),
        ];
    }
}
