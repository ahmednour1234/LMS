<?php

namespace App\Filament\Admin\Resources;

use App\Models\Setting;
use App\Filament\Admin\Resources\SettingResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'navigation.settings';

    protected static ?string $pluralModelLabel = 'navigation.settings';

    public static function getNavigationLabel(): string
    {
        return __('navigation.settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('settings.key')),
                Forms\Components\KeyValue::make('value')
                    ->label(__('settings.value')),
                Forms\Components\TextInput::make('group')
                    ->maxLength(255)
                    ->default('general')
                    ->label(__('settings.group')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->label(__('settings.key')),
                Tables\Columns\TextColumn::make('group')
                    ->searchable()
                    ->sortable()
                    ->label(__('settings.group')),
                Tables\Columns\TextColumn::make('value')
                    ->limit(50)
                    ->label(__('settings.value')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options(fn () => Setting::query()->distinct()->pluck('group', 'group')->toArray())
                    ->label(__('settings.group')),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}

