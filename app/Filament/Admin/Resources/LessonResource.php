<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Enums\LessonType;
use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use App\Filament\Admin\Resources\LessonResource\Pages;
use App\Filament\Admin\Resources\LessonResource\RelationManagers;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('navigation.lessons');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.lessons');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.lessons');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('section_id')
                    ->relationship('section', 'id', fn (Builder $query) => $query->whereHas('course.program', fn ($q) => $q->where('programs.branch_id', auth()->user()->branch_id ?? null))->orderBy('order'))
                    ->getOptionLabelUsing(function ($value): string {
                        if (is_object($value)) {
                            return MultilingualHelper::formatMultilingualField($value->title) ?: 'N/A';
                        }
                        $section = CourseSection::find($value);
                        return $section ? (MultilingualHelper::formatMultilingualField($section->title) ?: 'N/A') : 'N/A';
                    })
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return CourseSection::whereHas('course.program', fn ($q) => $q->where('programs.branch_id', auth()->user()->branch_id ?? null))
                            ->where(function (Builder $query) use ($search) {
                                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$search}%"])
                                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$search}%"]);
                            })
                            ->orderBy('order')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($section) {
                                return [$section->id => MultilingualHelper::formatMultilingualField($section->title)];
                            })
                            ->toArray();
                    })
                    ->preload()
                    ->required()
                    ->label(__('lessons.section')),
                Forms\Components\TextInput::make('title.ar')
                    ->label(__('lessons.title_ar'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title.en')
                    ->label(__('lessons.title_en'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description.ar')
                    ->label(__('lessons.description_ar'))
                    ->rows(3),
                Forms\Components\Textarea::make('description.en')
                    ->label(__('lessons.description_en'))
                    ->rows(3),
                Forms\Components\Select::make('lesson_type')
                    ->options([
                        LessonType::RECORDED->value => __('lessons.lesson_type_options.recorded'),
                        LessonType::LIVE->value => __('lessons.lesson_type_options.live'),
                        LessonType::MIXED->value => __('lessons.lesson_type_options.mixed'),
                    ])
                    ->default(LessonType::RECORDED->value)
                    ->required()
                    ->label(__('lessons.lesson_type')),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->label(__('lessons.sort_order')),
                Forms\Components\Toggle::make('is_preview')
                    ->label(__('lessons.is_preview'))
                    ->default(false),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('lessons.is_active'))
                    ->default(true),
                Forms\Components\TextInput::make('estimated_minutes')
                    ->numeric()
                    ->label(__('lessons.estimated_minutes')),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label(__('lessons.published_at')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('section.course.program', fn ($q) => $q->where('programs.branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('section.course.code')
                    ->sortable()
                    ->label(__('Course')),
                Tables\Columns\TextColumn::make('section.title')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->sortable()
                    ->label(__('Section')),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->searchable()
                    ->sortable()
                    ->label(__('Title')),
                Tables\Columns\TextColumn::make('lesson_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state?->value ?? $state) {
                        LessonType::RECORDED->value => __('Recorded'),
                        LessonType::LIVE->value => __('Live'),
                        LessonType::MIXED->value => __('Mixed'),
                        default => $state,
                    })
                    ->color(fn ($state) => match($state?->value ?? $state) {
                        LessonType::RECORDED->value => 'success',
                        LessonType::LIVE->value => 'warning',
                        LessonType::MIXED->value => 'info',
                        default => 'gray',
                    })
                    ->label(__('Type')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('Is Active')),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('Published At')),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label(__('Sort Order')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('section_id')
                    ->relationship('section', 'title', fn (Builder $query) => $query->whereHas('course.program', fn ($q) => $q->where('programs.branch_id', auth()->user()->branch_id ?? null)))
                    ->getOptionLabelUsing(function ($record): string {
                        if (is_object($record)) {
                            return MultilingualHelper::formatMultilingualField($record->title) ?: 'N/A';
                        }
                        $section = CourseSection::find($record);
                        return $section ? (MultilingualHelper::formatMultilingualField($section->title) ?: 'N/A') : 'N/A';
                    })
                    ->label(__('Section')),
                Tables\Filters\SelectFilter::make('lesson_type')
                    ->options([
                        LessonType::RECORDED->value => __('Recorded'),
                        LessonType::LIVE->value => __('Live'),
                        LessonType::MIXED->value => __('Mixed'),
                    ])
                    ->label(__('Lesson Type')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Is Active')),
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

    public static function getRelations(): array
    {
        return [
            // TODO: Enable relation managers in future phases when tables are created
            // RelationManagers\LessonVideosRelationManager::class,
            // RelationManagers\LessonDocumentsRelationManager::class,
            // RelationManagers\LessonTasksRelationManager::class,
            // RelationManagers\LessonQuizzesRelationManager::class,
            // RelationManagers\LessonMeetingsRelationManager::class,
            // RelationManagers\LessonReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'view' => Pages\ViewLesson::route('/{record}'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
