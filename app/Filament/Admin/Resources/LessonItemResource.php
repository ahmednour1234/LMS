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
     * Safe helper: resolve translated title stored as JSON array.
     */
    protected static function resolveTransTitle(?array $title, string $fallback = 'Untitled'): string
    {
        if (!is_array($title)) {
            return $fallback;
        }

        $locale = app()->getLocale();

        $value = $title[$locale]
            ?? $title['en']
            ?? $title['ar']
            ?? null;

        return (is_string($value) && trim($value) !== '') ? $value : $fallback;
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

                        // لو مش super admin: حصر الدروس حسب فرع الكورس
                        if ($user && method_exists($user, 'isSuperAdmin') && !$user->isSuperAdmin()) {
                            $query->whereHas('section.course', fn ($q) => $q->where('branch_id', $user->branch_id));
                        }

                        return $query->orderBy('id');
                    }
                )
                // دي اللي بتصلح label بتاع قائمة الـ options نفسها
                ->getOptionLabelFromRecordUsing(function ($record): string {
                    /** @var Lesson|null $record */
                    if (!$record) {
                        return 'Untitled';
                    }

                    return static::resolveTransTitle(
                        is_array($record->title ?? null) ? $record->title : null,
                        'Untitled'
                    );
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
                ->live(), // بدل reactive

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
                // مهم جداً: ما تعتمدش على original_filename لأنه ممكن يكون null
                ->relationship('mediaFile', 'id')
                // label آمن للقائمة نفسها
                ->getOptionLabelFromRecordUsing(function ($record): string {
                    /** @var MediaFile|null $record */
                    if (!$record) {
                        return 'Untitled File';
                    }

                    $filename = $record->original_filename
                        ?? $record->filename
                        ?? null;

                    return (is_string($filename) && trim($filename) !== '') ? $filename : 'Untitled File';
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

                if ($user && method_exists($user, 'isSuperAdmin') && !$user->isSuperAdmin()) {
                    $query->whereHas('lesson.section.course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('lesson.title')
                    ->label(__('lesson_items.lesson'))
                    ->formatStateUsing(function ($state): string {
                        return static::resolveTransTitle(is_array($state) ? $state : null, '');
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('lesson_items.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('lesson_items.type_options.' . $state)),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('lesson_items.title'))
                    ->formatStateUsing(function ($state): string {
                        return static::resolveTransTitle(is_array($state) ? $state : null, '');
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->label(__('lesson_items.order'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('lesson_items.is_active'))
                    ->boolean(),
            ])
            ->filters([
                // مهم: relationship('lesson','title') خطر لأن title JSON
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
                                $lesson->id => static::resolveTransTitle(
                                    is_array($lesson->title ?? null) ? $lesson->title : null,
                                    "Lesson #{$lesson->id}"
                                ),
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
