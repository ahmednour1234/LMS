<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\CostCenter;
use App\Filament\Admin\Resources\CostCenterResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CostCenterResource extends Resource
{
    protected static ?string $model = CostCenter::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Cost Centers';

    protected static ?string $modelLabel = 'Cost Center';

    protected static ?string $pluralModelLabel = 'Cost Centers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('cost_centers.name')),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('cost_centers.code')),
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('cost_centers.parent')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('cost_centers.is_active'))
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
                    ->label(__('cost_centers.code')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('cost_centers.name')),
                Tables\Columns\TextColumn::make('parent.name')
                    ->sortable()
                    ->label(__('cost_centers.parent')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('cost_centers.is_active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('cost_centers.is_active')),
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
            'index' => Pages\ListCostCenters::route('/'),
            'create' => Pages\CreateCostCenter::route('/create'),
            'edit' => Pages\EditCostCenter::route('/{record}/edit'),
        ];
    }
}

