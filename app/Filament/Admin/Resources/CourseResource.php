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
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('course_details')
                        ->label(__('courses.details'))
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
                            Forms\Components\FileUpload::make('image')
                                ->image()
                                ->directory('courses')
                                ->visibility('public')
                                ->nullable()
                                ->label(__('courses.image')),
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
                            Forms\Components\Toggle::make('is_active')
                                ->label(__('courses.is_active'))
                                ->default(true),
                        ]),
                    Forms\Components\Wizard\Step::make('pricing')
                        ->label(__('course_prices.pricing'))
                        ->schema([
                            Forms\Components\Select::make('price_delivery_type')
                                ->options([
                                    DeliveryType::Onsite->value => __('course_prices.delivery_type_options.onsite'),
                                    DeliveryType::Online->value => __('course_prices.delivery_type_options.online'),
                                ])
                                ->label(__('course_prices.delivery_type'))
                                ->helperText(__('course_prices.delivery_type_helper'))
                                ->nullable(),
                            Forms\Components\Select::make('pricing_mode')
                                ->options([
                                    'course_total' => __('course_prices.pricing_mode_options.course_total'),
                                    'per_session' => __('course_prices.pricing_mode_options.per_session'),
                                    'both' => __('course_prices.pricing_mode_options.both'),
                                ])
                                ->label(__('course_prices.pricing_mode'))
                                ->default('course_total')
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
                                ->helperText(__('course_prices.sessions_count_helper'))
                                ->live(onBlur: true),
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
                                ->maxValue(function (Forms\Get $get): int {
                                    $pricingMode = $get('pricing_mode') ?? 'course_total';
                                    if (in_array($pricingMode, ['per_session', 'both'])) {
                                        return (int) ($get('sessions_count') ?? 1);
                                    }
                                    return config('money.max_installments_limit', 36);
                                })
                                ->visible(fn (Forms\Get $get) => $get('allow_installments'))
                                ->required(fn (Forms\Get $get) => $get('allow_installments'))
                                ->helperText(function (Forms\Get $get): string {
                                    $pricingMode = $get('pricing_mode') ?? 'course_total';
                                    if (in_array($pricingMode, ['per_session', 'both'])) {
                                        $sessionsCount = (int) ($get('sessions_count') ?? 1);
                                        return __('course_prices.max_installments_helper_session_based', ['count' => $sessionsCount]);
                                    }
                                    $limit = config('money.max_installments_limit', 36);
                                    return __('course_prices.max_installments_helper_course_total', ['limit' => $limit]);
                                })
                                ->rules([
                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if ($value === null) {
                                            return;
                                        }
                                        $pricingMode = $get('pricing_mode') ?? 'course_total';
                                        $maxInstallments = (int) $value;
                                        
                                        if (in_array($pricingMode, ['per_session', 'both'])) {
                                            $sessionsCount = (int) ($get('sessions_count') ?? 1);
                                            if ($maxInstallments > $sessionsCount) {
                                                $fail(__('course_prices.max_installments_exceeds_sessions', ['count' => $sessionsCount]));
                                            }
                                        } else {
                                            $limit = config('money.max_installments_limit', 36);
                                            if ($maxInstallments > $limit) {
                                                $fail(__('course_prices.max_installments_exceeds_limit', ['limit' => $limit]));
                                            }
                                        }
                                    },
                                ]),
                            Forms\Components\Toggle::make('price_is_active')
                                ->label(__('course_prices.is_active'))
                                ->default(true),
                        ]),
                ])
                ->submitAction(Forms\Components\Actions\Action::make('create')
                    ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
                    ->submit('create')),
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
