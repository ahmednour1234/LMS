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

    public static function getNavigationLabel(): string { return __('navigation.lesson_items'); }
    public static function getModelLabel(): string { return __('navigation.lesson_items'); }
    public static function getPluralModelLabel(): string { return __('navigation.lesson_items'); }
    public static function getNavigationGroup(): ?string { return __('navigation.groups.training'); }

    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth('teacher')->id();
        $query = LessonItem::query()->setModel(new LessonItem());

        if (!$teacherId) return $query->whereRaw('1=0');

        return $query->whereHas('lesson.section.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId));
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

    public static function transValue($value, string $fallback = ''): string
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

            // ✅ Lesson select بدون relationship()
            Forms\Components\Select::make('lesson_id')
                ->label(__('lesson_items.lesson'))
                ->required()
                ->searchable()
                ->preload()
                ->options(function () use ($teacherId) {
                    if (!$teacherId) return [];
                    return Lesson::query()
                        ->whereHas('section.course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
                        ->orderBy('id')
                        ->limit(200)
                        ->get()
                        ->mapWithKeys(fn (Lesson $l) => [$l->id => static::transValue($l->title, "Lesson #{$l->id}")])
                        ->toArray();
                })
                ->getSearchResultsUsing(function (string $search) use ($teacherId) {
                    if (!$teacherId) return [];
                    return Lesson::query()
                        ->whereHas('section.course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
                        ->where(function ($q) use ($search) {
                            $q->where('title->en', 'like', "%{$search}%")
                              ->orWhere('title->ar', 'like', "%{$search}%");
                        })
                        ->orderBy('id')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Lesson $l) => [$l->id => static::transValue($l->title, "Lesson #{$l->id}")])
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (!$value) return null;
                    $lesson = Lesson::find($value);
                    return $lesson ? static::transValue($lesson->title, "Lesson #{$lesson->id}") : null;
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
             * ✅ Upload:
             * - Filament هيخزن الملف فعليًا في storage/app/media/...
             * - بعد الرفع مباشرة: نعمل "placeholder" عشان نفهم إن فيه upload جديد
             * - إنشاء MediaFile الحقيقي هيحصل وقت الحفظ في Pages
             */
            Forms\Components\FileUpload::make('media_upload')
                ->label(__('lesson_items.media_file'))
                ->disk('local')
                ->directory('media')
                ->visibility('private')
                ->acceptedFileTypes(['video/*', 'application/pdf', 'application/*', 'image/*'])
                ->maxSize(102400)
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true))
                ->dehydrated(true)
                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                    if (empty($state)) return;

                    // ✅ علّم إن فيه upload جديد
                    // (ده يخلي الـ select "يبقى متحكم فيه" بدل ما يفضل على القديم)
                    $set('media_file_id', '__uploaded__');
                })
                ->required(fn (Forms\Get $get) =>
                    in_array($get('type'), ['video', 'pdf', 'file'], true) && !$get('media_file_id')
                ),

            /**
             * ✅ Select ملف موجود (من ملفات المدرس فقط)
             */
            Forms\Components\Select::make('media_file_id')
                ->label(__('lesson_items.media_file'))
                ->searchable()
                ->preload()
                ->options(function () use ($teacherId) {
                    if (!$teacherId) return [];

                    return MediaFile::query()
                        ->where('teacher_id', $teacherId)
                        ->orderByDesc('id')
                        ->limit(200)
                        ->get()
                        ->mapWithKeys(function (MediaFile $m) {
                            $name = $m->original_filename ?: ($m->filename ?: "File #{$m->id}");
                            return [$m->id => $name];
                        })
                        ->toArray();
                })
                ->getSearchResultsUsing(function (string $search) use ($teacherId) {
                    if (!$teacherId) return [];

                    return MediaFile::query()
                        ->where('teacher_id', $teacherId)
                        ->where(function ($q) use ($search) {
                            $q->where('original_filename', 'like', "%{$search}%")
                              ->orWhere('filename', 'like', "%{$search}%");
                        })
                        ->orderByDesc('id')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function (MediaFile $m) {
                            $name = $m->original_filename ?: ($m->filename ?: "File #{$m->id}");
                            return [$m->id => $name];
                        })
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    // placeholder لا يطلع label
                    if (!$value || $value === '__uploaded__') return null;

                    $m = MediaFile::find($value);
                    return $m ? ($m->original_filename ?: ($m->filename ?: "File #{$m->id}")) : null;
                })
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true))
                ->required(fn (Forms\Get $get) => in_array($get('type'), ['video', 'pdf', 'file'], true))
                // ✅ لو placeholder بسبب رفع جديد، نخفي الـ select عشان ما يلخبطش
                ->hidden(fn (Forms\Get $get) => $get('media_file_id') === '__uploaded__'),

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
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('lesson_items.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('lesson_items.type_options.' . $state)),

                Tables\Columns\TextColumn::make('title_display')
                    ->label(__('lesson_items.title'))
                    ->getStateUsing(fn (LessonItem $r) => static::transValue($r->title, '')),

                Tables\Columns\TextColumn::make('order')->label(__('lesson_items.order'))->sortable(),

                Tables\Columns\IconColumn::make('is_active')->label(__('lesson_items.is_active'))->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
