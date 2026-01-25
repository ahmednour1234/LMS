<?php

namespace App\Filament\Teacher\Resources\Training;

use App\Domain\Media\Models\MediaFile;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\LessonItem;
use App\Filament\Teacher\Resources\Training\LessonItemResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonItemResource extends Resource
{
    protected static ?string $model = LessonItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Training';
    protected static ?int $navigationSort = 6;

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

    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth('teacher')->id();

        if (!$teacherId) {
            return LessonItem::query()->whereRaw('1=0');
        }

        return LessonItem::query()
            ->whereHas('lesson.section.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId));
    }


    protected static function normalizeTrans($value): array
    {
        if (is_array($value)) return $value;

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) return $decoded;
        }

        return [];
    }

    protected static function transValue($value, string $fallback = ''): string
    {
        $arr = static::normalizeTrans($value);
        $locale = app()->getLocale();

        $result = $arr[$locale] ?? $arr['en'] ?? $arr['ar'] ?? null;

        return (is_string($result) && trim($result) !== '') ? $result : $fallback;
    }

    public static function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();

        return $form->schema([
            Forms\Components\Hidden::make('teacher_id')->default($teacherId),

            Forms\Components\Select::make('lesson_id')
                ->label(__('lesson_items.lesson'))
                ->required()
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'lesson',
                    titleAttribute: 'id',
                    modifyQueryUsing: function (Builder $query) use ($teacherId) {
                        return $query
                            ->whereHas('section.course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
                            ->orderBy('id');
                    }
                )
                ->getOptionLabelFromRecordUsing(function ($record) {
                    if (is_object($record) && isset($record->title)) {
                        return static::transValue($record->title, 'Untitled');
                    }

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

            /**
             * Upload جديد:
             * - بنسيبه يرفع ويطلع path
             * - وهنحوّله لـ MediaFile record في Create/Edit Pages
             */
            Forms\Components\FileUpload::make('media_upload')
                ->label(__('lesson_items.media_file'))
                ->disk('local')
                ->directory('media')
                ->visibility('private')
                ->acceptedFileTypes(['video/*', 'application/pdf', 'application/*'])
                ->maxSize(102400)
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true))
                ->dehydrated(true) // مهم: نخليها تدخل الداتا عشان نلقطها في Pages
                ->required(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true) && !$get('media_file_id')),

            /**
             * اختيار ملف موجود:
             * يظهر لو مفيش Upload
             */
            Forms\Components\Select::make('media_file_id')
                ->label(__('lesson_items.media_file'))
                ->searchable()
                ->preload()
                ->relationship('mediaFile', 'id', modifyQueryUsing: fn (Builder $q) => $q->where('teacher_id', $teacherId))
                ->getOptionLabelFromRecordUsing(function ($record): string {
                    if (!$record) return 'Untitled File';

                    $name = $record->original_filename ?? $record->filename ?? null;

                    return (is_string($name) && trim($name) !== '') ? $name : 'Untitled File';
                })
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true) && !$get('media_upload'))
                ->required(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true) && !$get('media_upload')),

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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['lesson', 'mediaFile']))
            ->columns([
                Tables\Columns\TextColumn::make('lesson_display')
                    ->label(__('lesson_items.lesson'))
                    ->getStateUsing(function (LessonItem $record) {
                        $record->loadMissing('lesson');

                        if ($record->lesson && isset($record->lesson->title)) {
                            $title = static::transValue($record->lesson->title, '');
                            if ($title !== '') return $title;
                        }

                        return $record->lesson_id ? "Lesson #{$record->lesson_id}" : '';
                    })
                    ->sortable(query: fn (Builder $q, string $dir) => $q->orderBy('lesson_id', $dir)),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('lesson_items.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('lesson_items.type_options.' . $state)),

                Tables\Columns\TextColumn::make('title_display')
                    ->label(__('lesson_items.title'))
                    ->getStateUsing(fn (LessonItem $r) => static::transValue($r->title, ''))
                    ->searchable(query: function (Builder $q, string $search) {
                        $q->where('title->en', 'like', "%{$search}%")
                          ->orWhere('title->ar', 'like', "%{$search}%");
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
                        $teacherId = auth('teacher')->id();

                        return Lesson::query()
                            ->whereHas('section.course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
                            ->orderBy('id')
                            ->get()
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
