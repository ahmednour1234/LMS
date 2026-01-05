<?php

namespace App\Filament\Admin\Resources;

use App\Models\Setting;
use App\Filament\Admin\Resources\SettingResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'settings';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('navigation.settings');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.settings');
    }

    public static function getPluralModelLabel(): string
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
                    ->disabled()
                    ->dehydrated(false)
                    ->label(__('settings.key')),
                Forms\Components\KeyValue::make('value')
                    ->label(__('settings.value'))
                    ->rules(function (Setting $record) {
                        if (!$record->exists) {
                            return [];
                        }

                        $key = $record->key;
                        $rules = [];

                        switch ($key) {
                            case 'tax_rate':
                                $rules = ['value.rate' => ['required', 'numeric', 'min:0', 'max:1']];
                                break;
                            case 'currency':
                                $rules = ['value' => ['required', 'array'], 'value.code' => ['required', 'string'], 'value.symbol' => ['required', 'string']];
                                break;
                            case 'invoice_prefix':
                            case 'receipt_prefix':
                                $rules = ['value.prefix' => ['required', 'string', 'max:10']];
                                break;
                            case 'app_email':
                                $rules = ['value.email' => ['required', 'email']];
                                break;
                            case 'app_phone':
                            case 'app_whatsapp':
                                $rules = ['value.phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/']];
                                break;
                            case 'fiscal_year_start':
                            case 'fiscal_year_end':
                                $rules = [
                                    'value.month' => ['required', 'integer', 'min:1', 'max:12'],
                                    'value.day' => ['required', 'integer', 'min:1', 'max:31'],
                                ];
                                break;
                            case 'tax_registration_number':
                            case 'commercial_registration_number':
                                $rules = ['value' => ['required', 'array'], 'value.number' => ['required', 'string']];
                                break;
                            default:
                                $rules = ['value' => ['required']];
                        }

                        return $rules;
                    }),
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
                Tables\Columns\IconColumn::make('is_system')
                    ->boolean()
                    ->label(__('settings.is_system')),
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
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label(__('settings.is_system')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Setting $record) => !$record->isSystemSetting() || auth()->user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
