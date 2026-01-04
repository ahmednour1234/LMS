<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\CoursePrice;
use App\Filament\Admin\Resources\CoursePriceResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CoursePriceResource extends Resource
{
    protected static ?string $model = CoursePrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('navigation.course_prices');
    }

    public static function getModelLabel(): string
    {
        return __('course_prices.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.course_prices');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => $query->where('branch_id', auth()->user()->branch_id ?? null))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('course_prices.course')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('course_prices.branch'))
                    ->helperText(__('course_prices.branch_helper'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\Select::make('delivery_type')
                    ->options([
                        DeliveryType::Onsite->value => __('course_prices.delivery_type_options.onsite'),
                        DeliveryType::Online->value => __('course_prices.delivery_type_options.online'),
                        DeliveryType::Virtual->value => __('course_prices.delivery_type_options.virtual'),
                    ])
                    ->label(__('course_prices.delivery_type'))
                    ->helperText(__('course_prices.delivery_type_helper')),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->label(__('course_prices.price')),
                Forms\Components\Toggle::make('allow_installments')
                    ->label(__('course_prices.allow_installments'))
                    ->default(false)
                    ->live(),
                Forms\Components\TextInput::make('min_down_payment')
                    ->numeric()
                    ->prefix('$')
                    ->label(__('course_prices.min_down_payment'))
                    ->visible(fn (Forms\Get $get) => $get('allow_installments')),
                Forms\Components\TextInput::make('max_installments')
                    ->numeric()
                    ->label(__('course_prices.max_installments'))
                    ->visible(fn (Forms\Get $get) => $get('allow_installments')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('course_prices.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->sortable()
                    ->label(__('course_prices.course')),
                Tables\Columns\TextColumn::make('course.name')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->label(__('course_prices.course_name')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('course_prices.branch'))
                    ->placeholder(__('course_prices.global'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('delivery_type')
                    ->formatStateUsing(fn ($state) => $state ? __('course_prices.delivery_type_options.' . $state) : __('course_prices.all_delivery_types'))
                    ->badge()
                    ->label(__('course_prices.delivery_type')),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable()
                    ->label(__('course_prices.price')),
                Tables\Columns\IconColumn::make('allow_installments')
                    ->boolean()
                    ->label(__('course_prices.allow_installments')),
                Tables\Columns\TextColumn::make('min_down_payment')
                    ->money()
                    ->label(__('course_prices.min_down_payment'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('max_installments')
                    ->label(__('course_prices.max_installments'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('course_prices.is_active')),
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
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'code')
                    ->label(__('course_prices.course')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('course_prices.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\SelectFilter::make('delivery_type')
                    ->options([
                        DeliveryType::Onsite->value => __('course_prices.delivery_type_options.onsite'),
                        DeliveryType::Online->value => __('course_prices.delivery_type_options.online'),
                        DeliveryType::Virtual->value => __('course_prices.delivery_type_options.virtual'),
                    ])
                    ->label(__('course_prices.delivery_type')),
                Tables\Filters\TernaryFilter::make('allow_installments')
                    ->label(__('course_prices.allow_installments')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('course_prices.is_active')),
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
            'index' => Pages\ListCoursePrices::route('/'),
            'create' => Pages\CreateCoursePrice::route('/create'),
            'edit' => Pages\EditCoursePrice::route('/{record}/edit'),
        ];
    }
}
