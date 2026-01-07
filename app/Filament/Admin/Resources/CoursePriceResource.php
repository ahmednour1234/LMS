<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\CoursePrice;
use App\Filament\Admin\Resources\CoursePriceResource\Pages;
use App\Support\Helpers\MultilingualHelper;
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
                Forms\Components\Select::make('pricing_mode')
                    ->options([
                        'course_total' => __('course_prices.pricing_mode_options.course_total'),
                        'per_session' => __('course_prices.pricing_mode_options.per_session'),
                        'both' => __('course_prices.pricing_mode_options.both'),
                    ])
                    ->label(__('course_prices.pricing_mode'))
                    ->default('course_total')
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix(config('money.symbol', 'ر.ع'))
                    ->label(__('course_prices.price'))
                    ->minValue(0.001)
                    ->step(0.001)
                    ->visible(fn (Forms\Get $get) => in_array($get('pricing_mode'), ['course_total', 'both']))
                    ->required(fn (Forms\Get $get) => in_array($get('pricing_mode'), ['course_total', 'both']))
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('session_price')
                    ->numeric()
                    ->prefix(config('money.symbol', 'ر.ع'))
                    ->label(__('course_prices.session_price'))
                    ->helperText(__('course_prices.session_price_helper'))
                    ->minValue(0.001)
                    ->step(0.001)
                    ->visible(fn (Forms\Get $get) => in_array($get('pricing_mode'), ['per_session', 'both']))
                    ->required(fn (Forms\Get $get) => in_array($get('pricing_mode'), ['per_session', 'both'])),
                Forms\Components\TextInput::make('sessions_count')
                    ->numeric()
                    ->integer()
                    ->label(__('course_prices.sessions_count'))
                    ->visible(fn (Forms\Get $get) => in_array($get('pricing_mode'), ['per_session', 'both']))
                    ->minValue(1)
                    ->required(fn (Forms\Get $get) => in_array($get('pricing_mode'), ['per_session', 'both']))
                    ->helperText(__('course_prices.sessions_count_helper')),
                Forms\Components\Toggle::make('allow_installments')
                    ->label(__('course_prices.allow_installments'))
                    ->default(false)
                    ->live()
                    ->disabled(fn (Forms\Get $get) => $get('pricing_mode') === 'per_session')
                    ->helperText(fn (Forms\Get $get) => $get('pricing_mode') === 'per_session' 
                        ? __('course_prices.installments_disabled_for_per_session')
                        : null),
                Forms\Components\TextInput::make('min_down_payment')
                    ->numeric()
                    ->prefix(config('money.symbol', 'ر.ع'))
                    ->label(__('course_prices.min_down_payment'))
                    ->minValue(0)
                    ->step(0.001)
                    ->visible(fn (Forms\Get $get) => $get('allow_installments'))
                    ->rules([
                        fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $price = (float) $get('price');
                            if ($value !== null && $price > 0 && (float) $value > $price) {
                                $fail(__('course_prices.min_down_payment_exceeds_price'));
                            }
                        },
                    ])
                    ->helperText(__('course_prices.min_down_payment_helper')),
                Forms\Components\TextInput::make('max_installments')
                    ->numeric()
                    ->integer()
                    ->label(__('course_prices.max_installments'))
                    ->minValue(1)
                    ->maxValue(24)
                    ->visible(fn (Forms\Get $get) => $get('allow_installments'))
                    ->required(fn (Forms\Get $get) => $get('allow_installments'))
                    ->helperText(__('course_prices.max_installments_helper')),
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
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->label(__('course_prices.course_name')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('course_prices.branch'))
                    ->placeholder(__('course_prices.global'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('delivery_type')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return __('course_prices.all_delivery_types');
                        }
                        $value = $state instanceof DeliveryType ? $state->value : (string) $state;
                        return __('course_prices.delivery_type_options.' . $value);
                    })
                    ->badge()
                    ->label(__('course_prices.delivery_type')),
                Tables\Columns\TextColumn::make('pricing_mode')
                    ->formatStateUsing(fn ($state) => $state ? __('course_prices.pricing_mode_options.' . $state) : '-')
                    ->badge()
                    ->label(__('course_prices.pricing_mode')),
                Tables\Columns\TextColumn::make('price')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('course_prices.price'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('session_price')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('course_prices.session_price'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('sessions_count')
                    ->label(__('course_prices.sessions_count'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('allow_installments')
                    ->boolean()
                    ->label(__('course_prices.allow_installments')),
                Tables\Columns\TextColumn::make('min_down_payment')
                    ->money('OMR')
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
