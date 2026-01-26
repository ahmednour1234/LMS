<?php

namespace App\Filament\Teacher\Resources\Training;

use App\Domain\Training\Enums\LessonType;
use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use App\Filament\Teacher\Resources\Training\LessonResource\Pages;
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

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Training';
    protected static ?int $navigationSort = 5;

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

    /**
     * ✅ Helper: Build section label:
     * [COURSECODE(or Course#ID)] #SECTION_ID - AR_TITLE / EN_TITLE
     */
    protected static function sectionLabel(?CourseSection $section): string
    {
        if (!$section) {
            return 'N/A';
        }

        $course = $section->course;
        $courseCodeOrId = $course?->code ?: ('Course#' . ($course?->id ?? 'N/A'));

        $ar = data_get($section->title, 'ar');
        $en = data_get($section->title, 'en');

        $ar = is_string($ar) && trim($ar) !== '' ? $ar : '—';
        $en = is_string($en) && trim($en) !== '' ? $en : '—';

        return "[{$courseCodeOrId}] #{$section->id} - {$ar} / {$en}";
    }

    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth('teacher')->id();

        return parent::getEloquentQuery()
            ->whereHas('section.course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId));
    }

    public static function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();

        return $form->schema([
            Forms\Components\Select::make('section_id')
                ->label(__('lessons.section'))
                ->relationship(
                    name: 'section',
                    titleAttribute: 'id',
                    modifyQueryUsing: fn (Builder $query) => $query
                        ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
                        ->with('course:id,code') // ✅ مهم عشان label يبقى سريع
                        ->orderBy('order')
                )
                // ✅ عرض اللابل (Course Code + Section ID + ar/en)
                ->getOptionLabelFromRecordUsing(fn (CourseSection $record) => static::sectionLabel($record))
                // ✅ بحث مخصص على ar/en + course code + id
                ->searchable()
                ->getSearchResultsUsing(function (string $search) use ($teacherId): array {
                    return CourseSection::query()
                        ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', $teacherId))
                        ->with('course:id,code')
                        ->where(function (Builder $q) use ($search) {
                            $q->where('id', $search)
                              ->orWhereHas('course', fn (Builder $cq) => $cq->where('code', 'like', "%{$search}%"))
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$search}%"]);
                        })
                        ->orderBy('order')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (CourseSection $section) => [
                            $section->id => static::sectionLabel($section),
                        ])
                        ->toArray();
                })
                ->preload()
                ->required(),

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
                ->label(__('lessons.lesson_type'))
                ->options([
                    LessonType::RECORDED->value => __('lessons.lesson_type_options.recorded'),
                    LessonType::LIVE->value     => __('lessons.lesson_type_options.live'),
                    LessonType::MIXED->value    => __('lessons.lesson_type_options.mixed'),
                ])
                ->default(LessonType::RECORDED->value)
                ->required(),

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
                    ->formatStateUsing(fn ($state) => match ($state?->value ?? $state) {
                        LessonType::RECORDED->value => __('Recorded'),
                        LessonType::LIVE->value     => __('Live'),
                        LessonType::MIXED->value    => __('Mixed'),
                        default                     => $state,
                    })
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        LessonType::RECORDED->value => 'success',
                        LessonType::LIVE->value     => 'warning',
                        LessonType::MIXED->value    => 'info',
                        default                     => 'gray',
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
                // ✅ Filter Section: نفس شكل اللابل (Course Code + Section ID + ar/en)
                Tables\Filters\SelectFilter::make('section_id')
                    ->label(__('Section'))
                    ->relationship(
                        name: 'section',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->whereHas('course', fn (Builder $q) => $q->where('owner_teacher_id', auth('teacher')->id()))
                            ->with('course:id,code')
                            ->orderBy('order')
                    )
                    ->getOptionLabelFromRecordUsing(fn (CourseSection $record) => static::sectionLabel($record))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('lesson_type')
                    ->options([
                        LessonType::RECORDED->value => __('Recorded'),
                        LessonType::LIVE->value     => __('Live'),
                        LessonType::MIXED->value    => __('Mixed'),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'view'   => Pages\ViewLesson::route('/{record}'),
            'edit'   => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
