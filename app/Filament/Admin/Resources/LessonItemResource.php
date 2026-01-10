<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Media\Models\MediaFile;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\LessonItem;
use App\Filament\Admin\Resources\LessonItemResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonItemResource extends Resource
{
    protected static ?string $model = LessonItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationGroup = 'training';
    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('navigation.lesson_items');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.lesson_items');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.lesson_items');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    /**
     * Normalize translated JSON field to array مهما كان نوعه:
     * - array (ممتاز)
     * - json string
     * - null
     */
    protected static function normalizeTrans($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    protected static function transValue($value, string $fallback = ''): string
    {
        $arr = static::normalizeTrans($value);
        $locale = app()->getLocale();

        $result = $arr[$locale]
            ?? $arr['en']
            ?? $arr['ar']
            ?? null;

        return (is_string($result) && trim($result) !== '') ? $result : $fallback;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('lesson_id')
                ->label(__('lesson_items.lesson'))
                ->required()
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'lesson',
                    titleAttribute: 'id',
                    modifyQueryUsing: function (Builder $query) {
                        $user = auth()->user();

                        if ($user && method_exists($user, 'isSuperAdmin') && !$user->isSuperAdmin()) {
                            $query->whereHas('section.course.program', fn ($q) => $q->where('programs.branch_id', $user->branch_id));
                        }

                        return $query->orderBy('id');
                    }
                )
                ->getOptionLabelFromRecordUsing(function ($record) {
                    // Handle both object and ID cases
                    if (is_object($record) && isset($record->title)) {
                        return static::transValue($record->title, 'Untitled');
                    }

                    // If it's an ID, load the lesson
                    if (is_numeric($record)) {
                        $lesson = Lesson::find($record);
                        if ($lesson && isset($lesson->title)) {
                            return static::transValue($lesson->title, 'Untitled');
                        }
                    }

                    return 'Untitled';
                }),

            Forms\Components\Select::make('type')
                ->label(__('lesson_items.type'))
                ->options([
                    'video' => __('lesson_items.type_options.video'),
                    'pdf'   => __('lesson_items.type_options.pdf'),
                    'file'  => __('lesson_items.type_options.file'),
                    'link'  => __('lesson_items.type_options.link'),
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('title.ar')
                ->label(__('lesson_items.title_ar'))
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('title.en')
                ->label(__('lesson_items.title_en'))
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('media_file_id')
                ->label(__('lesson_items.media_file'))
                ->searchable()
                ->preload()
                ->relationship('mediaFile', 'id') // id آمن
                ->getOptionLabelFromRecordUsing(function ($record): string {
                    if (!$record) return 'Untitled File';

                    $name = $record->original_filename
                        ?? $record->filename
                        ?? null;

                    return (is_string($name) && trim($name) !== '') ? $name : 'Untitled File';
                })
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true))
                ->required(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true)),

            Forms\Components\TextInput::make('external_url')
                ->label(__('lesson_items.external_url'))
                ->url()
                ->visible(fn (Forms\Get $get) => $get('type') === 'link')
                ->required(fn (Forms\Get $get) => $get('type') === 'link'),

            Forms\Components\TextInput::make('order')
                ->label(__('lesson_items.order'))
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_active')
                ->label(__('lesson_items.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                // Eager load relationships to avoid N+1 queries
                $query->with(['lesson', 'mediaFile']);

                // Apply branch filter if user is not super admin
                if ($user && method_exists($user, 'isSuperAdmin') && !$user->isSuperAdmin()) {
                    $query->whereHas('lesson.section.course.program', fn ($q) => $q->where('programs.branch_id', $user->branch_id));
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('lesson_display')
                    ->label(__('lesson_items.lesson'))
                    ->getStateUsing(function (LessonItem $record) {
                        // Ensure lesson is loaded
                        if (!$record->relationLoaded('lesson')) {
                            $record->load('lesson');
                        }

                        // Get lesson title
                        if ($record->lesson && isset($record->lesson->title)) {
                            $title = static::transValue($record->lesson->title, '');
                            if ($title !== '') {
                                return $title;
                            }
                        }

                        // Fallback: show lesson ID if title is empty
                        return $record->lesson_id ? "Lesson #{$record->lesson_id}" : '';
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy('lesson_id', $direction);
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('lesson_items.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('lesson_items.type_options.' . $state)),

                Tables\Columns\TextColumn::make('title_display')
                    ->label(__('lesson_items.title'))
                    ->getStateUsing(fn (LessonItem $record) => static::transValue($record->title, ''))
                    ->searchable(query: function (Builder $query, string $search) {
                        // بحث داخل JSON (MySQL) لو محتاج:
                        $query->where('title->en', 'like', "%{$search}%")
                              ->orWhere('title->ar', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy('id', $direction);
                    }),

                Tables\Columns\TextColumn::make('order')
                    ->label(__('lesson_items.order'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('lesson_items.is_active'))
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lesson_id')
                    ->label(__('lesson_items.lesson'))
                    ->options(function () {
                        $user = auth()->user();

                        $q = Lesson::query()->orderBy('id');

                        if ($user && method_exists($user, 'isSuperAdmin') && !$user->isSuperAdmin()) {
                            $q->whereHas('section.course', fn ($x) => $x->where('branch_id', $user->branch_id));
                        }

                        return $q->get()
                            ->mapWithKeys(fn (Lesson $lesson) => [
                                $lesson->id => static::transValue($lesson->title, "Lesson #{$lesson->id}"),
                            ])
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('type')
                    ->label(__('lesson_items.type'))
                    ->options([
                        'video' => __('lesson_items.type_options.video'),
                        'pdf'   => __('lesson_items.type_options.pdf'),
                        'file'  => __('lesson_items.type_options.file'),
                        'link'  => __('lesson_items.type_options.link'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('lesson_items.is_active')),
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
            'index'  => Pages\ListLessonItems::route('/'),
            'create' => Pages\CreateLessonItem::route('/create'),
            'view'   => Pages\ViewLessonItem::route('/{record}'),
            'edit'   => Pages\EditLessonItem::route('/{record}/edit'),
        ];
    }
}
