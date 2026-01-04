<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use App\Filament\Admin\Resources\LessonResource\Pages;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('section_id')
                    ->relationship('section', 'id', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? null))->orderBy('id'))
                    ->getOptionLabelUsing(fn ($record): ?string => is_object($record) ? ($record->title[app()->getLocale()] ?? $record->title['en'] ?? null) : (\App\Domain\Training\Models\CourseSection::find($record)?->title[app()->getLocale()] ?? \App\Domain\Training\Models\CourseSection::find($record)?->title['en'] ?? null))
                    ->searchable()
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
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->label(__('lessons.order')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('lessons.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('section.course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('section.title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->sortable()
                    ->label(__('lessons.section')),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->searchable()
                    ->sortable()
                    ->label(__('lessons.title')),
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label(__('lessons.order')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('lessons.is_active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('section_id')
                    ->relationship('section', 'title')
                    ->label(__('lessons.section')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('lessons.is_active')),
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
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'view' => Pages\ViewLesson::route('/{record}'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
