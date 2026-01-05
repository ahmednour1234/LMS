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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('lesson_id')
                    ->relationship('lesson', 'id', fn (Builder $query) => $query->whereHas('section.course', fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? null))->orderBy('id'))
                    ->getOptionLabelUsing(function ($record): string {
                        $title = 'Untitled';
                        if (is_object($record)) {
                            $locale = app()->getLocale();
                            $title = $record->title[$locale] 
                                ?? $record->title['en'] 
                                ?? $record->title['ar'] 
                                ?? null;
                            
                            if (empty($title) || !is_string($title)) {
                                $title = 'Untitled';
                            }
                        } else {
                            $lesson = \App\Domain\Training\Models\Lesson::find($record);
                            if ($lesson) {
                                $locale = app()->getLocale();
                                $title = $lesson->title[$locale] 
                                    ?? $lesson->title['en'] 
                                    ?? $lesson->title['ar'] 
                                    ?? null;
                                
                                if (empty($title) || !is_string($title)) {
                                    $title = 'Untitled';
                                }
                            }
                        }
                        return (string) $title;
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('lesson_items.lesson')),
                Forms\Components\Select::make('type')
                    ->options([
                        'video' => __('lesson_items.type_options.video'),
                        'pdf' => __('lesson_items.type_options.pdf'),
                        'file' => __('lesson_items.type_options.file'),
                        'link' => __('lesson_items.type_options.link'),
                    ])
                    ->required()
                    ->label(__('lesson_items.type'))
                    ->reactive(),
                Forms\Components\TextInput::make('title.ar')
                    ->label(__('lesson_items.title_ar'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title.en')
                    ->label(__('lesson_items.title_en'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('media_file_id')
                    ->relationship('mediaFile', 'original_filename')
                    ->searchable()
                    ->preload()
                    ->label(__('lesson_items.media_file'))
                    ->visible(fn ($get) => in_array($get('type'), ['video', 'pdf', 'file'])),
                Forms\Components\TextInput::make('external_url')
                    ->url()
                    ->label(__('lesson_items.external_url'))
                    ->visible(fn ($get) => $get('type') === 'link'),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->label(__('lesson_items.order')),
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
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('lesson.section.course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('lesson.title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->sortable()
                    ->label(__('lesson_items.lesson')),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('lesson_items.type_options.' . $state))
                    ->label(__('lesson_items.type')),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->searchable()
                    ->sortable()
                    ->label(__('lesson_items.title')),
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label(__('lesson_items.order')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('lesson_items.is_active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->label(__('lesson_items.lesson')),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'video' => __('lesson_items.type_options.video'),
                        'pdf' => __('lesson_items.type_options.pdf'),
                        'file' => __('lesson_items.type_options.file'),
                        'link' => __('lesson_items.type_options.link'),
                    ])
                    ->label(__('lesson_items.type')),
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
            'index' => Pages\ListLessonItems::route('/'),
            'create' => Pages\CreateLessonItem::route('/create'),
            'view' => Pages\ViewLessonItem::route('/{record}'),
            'edit' => Pages\EditLessonItem::route('/{record}/edit'),
        ];
    }
}
