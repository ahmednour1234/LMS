<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\PaymentMethod;
use App\Filament\Admin\Resources\PaymentMethodResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'navigation.payment_methods';

    protected static ?string $pluralModelLabel = 'navigation.payment_methods';

    public static function getNavigationLabel(): string
    {
        return __('navigation.payment_methods');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('payment_methods.name')),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('payment_methods.code')),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'cash' => __('payment_methods.type_options.cash'),
                        'bank_transfer' => __('payment_methods.type_options.bank_transfer'),
                        'card' => __('payment_methods.type_options.card'),
                        'gateway' => __('payment_methods.type_options.gateway'),
                    ])
                    ->label(__('payment_methods.type')),
                Forms\Components\KeyValue::make('config')
                    ->label(__('payment_methods.config')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('payment_methods.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('payment_methods.name')),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label(__('payment_methods.code')),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label(__('payment_methods.type')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('payment_methods.is_active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'cash' => __('payment_methods.type_options.cash'),
                        'bank_transfer' => __('payment_methods.type_options.bank_transfer'),
                        'card' => __('payment_methods.type_options.card'),
                        'gateway' => __('payment_methods.type_options.gateway'),
                    ])
                    ->label(__('payment_methods.type')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('payment_methods.is_active')),
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}

