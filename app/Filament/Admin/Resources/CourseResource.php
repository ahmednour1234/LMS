<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Filament\Admin\Resources\CourseResource\Pages;
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
                    ->relationship('program', 'code', fn (Builder $query) => $query->where('branch_id', auth()->user()->branch_id ?? null))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('courses.program')),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                        $branchId = auth()->user()->isSuperAdmin() ? $get('branch_id') : auth()->user()->branch_id;
                        return $rule->where('branch_id', $branchId);
                    })
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
                Forms\Components\Select::make('delivery_type')
                    ->options([
                        DeliveryType::Onsite->value => __('courses.delivery_type_options.onsite'),
                        DeliveryType::Online->value => __('courses.delivery_type_options.online'),
                        DeliveryType::Virtual->value => __('courses.delivery_type_options.virtual'),
                    ])
                    ->required()
                    ->label(__('courses.delivery_type')),
                Forms\Components\TextInput::make('duration_hours')
                    ->numeric()
                    ->label(__('courses.duration_hours')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('courses.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\Select::make('trainers')
                    ->relationship('trainers', 'name', fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'trainer')))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label(__('courses.trainers')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('courses.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->where('branch_id', $user->branch_id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label(__('courses.code')),
                Tables\Columns\TextColumn::make('name')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->searchable()
                    ->sortable()
                    ->label(__('courses.name')),
                Tables\Columns\TextColumn::make('program.code')
                    ->sortable()
                    ->label(__('courses.program')),
                Tables\Columns\TextColumn::make('delivery_type')
                    ->formatStateUsing(fn ($state) => __('courses.delivery_type_options.' . $state))
                    ->badge()
                    ->label(__('courses.delivery_type')),
                Tables\Columns\TextColumn::make('duration_hours')
                    ->label(__('courses.duration_hours'))
                    ->suffix(' ' . __('courses.hours')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('courses.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('trainers_count')
                    ->counts('trainers')
                    ->label(__('courses.trainers_count')),
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
                        DeliveryType::Virtual->value => __('courses.delivery_type_options.virtual'),
                    ])
                    ->label(__('courses.delivery_type')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('courses.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('courses.is_active')),
            ])
            ->actions([
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
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
