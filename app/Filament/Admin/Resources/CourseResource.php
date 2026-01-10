<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Teacher;
use App\Filament\Admin\Resources\CourseResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('navigation.courses');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.courses');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.courses');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('program_id')
                    ->relationship('program', 'code')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('courses.program')),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('courses.code')),
                Forms\Components\TextInput::make('name.ar')
                    ->label(__('courses.name_ar'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name.en')
                    ->label(__('courses.name_en'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description.ar')
                    ->label(__('courses.description_ar'))
                    ->rows(3),
                Forms\Components\Textarea::make('description.en')
                    ->label(__('courses.description_en'))
                    ->rows(3),
                Forms\Components\Section::make(__('courses.image'))
                    ->schema([
                        Forms\Components\FileUpload::make('image.ar')
                            ->image()
                            ->directory('courses')
                            ->visibility('public')
                            ->nullable()
                            ->label(__('courses.image_ar')),
                        Forms\Components\FileUpload::make('image.en')
                            ->image()
                            ->directory('courses')
                            ->visibility('public')
                            ->nullable()
                            ->label(__('courses.image_en')),
                    ])
                    ->collapsible(),
                Forms\Components\Select::make('delivery_type')
                    ->options([
                        DeliveryType::Onsite->value => __('courses.delivery_type_options.onsite'),
                        DeliveryType::Online->value => __('courses.delivery_type_options.online'),
                        DeliveryType::Hybrid->value => __('courses.delivery_type_options.hybrid'),
                    ])
                    ->required()
                    ->label(__('courses.delivery_type')),
                Forms\Components\TextInput::make('duration_hours')
                    ->numeric()
                    ->label(__('courses.duration_hours')),
                Forms\Components\Select::make('owner_teacher_id')
                    ->relationship('ownerTeacher', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('Owner Teacher')),
                Forms\Components\Select::make('teachers')
                    ->relationship('teachers', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label(__('Additional Teachers')),
                // Trainers functionality removed - use teachers() relationship instead
                // Forms\Components\Select::make('trainers')
                //     ->relationship('trainers', 'name', fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'trainer')))
                //     ->multiple()
                //     ->searchable()
                //     ->preload()
                //     ->label(__('courses.trainers')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('courses.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label(__('courses.code')),
                Tables\Columns\TextColumn::make('name')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->searchable()
                    ->sortable()
                    ->label(__('courses.name')),
                Tables\Columns\ImageColumn::make('image')
                    ->getStateUsing(function ($record) {
                        $locale = app()->getLocale();
                        return $record->image[$locale] ?? $record->image['ar'] ?? $record->image['en'] ?? null;
                    })
                    ->label(__('courses.image'))
                    ->circular(),
                Tables\Columns\TextColumn::make('program.code')
                    ->sortable()
                    ->label(__('courses.program')),
                Tables\Columns\TextColumn::make('delivery_type')
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof DeliveryType ? $state->value : (string) $state;
                        return __('courses.delivery_type_options.' . $value);
                    })
                    ->badge()
                    ->label(__('courses.delivery_type')),
                Tables\Columns\TextColumn::make('duration_hours')
                    ->label(__('courses.duration_hours'))
                    ->suffix(' ' . __('courses.hours')),
                Tables\Columns\TextColumn::make('ownerTeacher.name')
                    ->sortable()
                    ->label(__('Owner Teacher')),
                Tables\Columns\TextColumn::make('teachers_count')
                    ->counts('teachers')
                    ->label(__('Additional Teachers'))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('courses.is_active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('program_id')
                    ->relationship('program', 'code')
                    ->label(__('courses.program')),
                Tables\Filters\SelectFilter::make('delivery_type')
                    ->options([
                        DeliveryType::Onsite->value => __('courses.delivery_type_options.onsite'),
                        DeliveryType::Online->value => __('courses.delivery_type_options.online'),
                        DeliveryType::Hybrid->value => __('courses.delivery_type_options.hybrid'),
                    ])
                    ->label(__('courses.delivery_type')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('courses.is_active')),
            ])
            ->actions([
                Tables\Actions\Action::make('studio')
                    ->label(__('Course Studio'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->button()
                    ->url(fn ($record) => static::getUrl('studio', ['record' => $record]))
                    ->visible(fn () => auth()->user()->isSuperAdmin() || auth()->user()->hasRole('admin')),
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
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
            'studio' => Pages\ManageCourseStudio::route('/{record}/studio'),
        ];
    }
}
