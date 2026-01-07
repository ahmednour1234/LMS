<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\Account;
use App\Domain\Branch\Models\Branch;
use App\Filament\Admin\Resources\AccountResource\Pages;
use App\Filament\Concerns\HasTableExports;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountResource extends Resource
{
    use HasTableExports;
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('navigation.accounts');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.accounts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.accounts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('accounts.code')),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('accounts.name')),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'asset' => __('accounts.type_options.asset'),
                        'liability' => __('accounts.type_options.liability'),
                        'equity' => __('accounts.type_options.equity'),
                        'revenue' => __('accounts.type_options.revenue'),
                        'expense' => __('accounts.type_options.expense'),
                    ])
                    ->label(__('accounts.type')),
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('accounts.parent')),
                Forms\Components\TextInput::make('opening_balance')
                    ->numeric()
                    ->default(0)
                    ->label(__('accounts.opening_balance')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('accounts.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('accounts.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->visibleTo(auth()->user());
            })
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label(__('accounts.code')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('accounts.name')),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('accounts.type_options.' . $state))
                    ->label(__('accounts.type')),
                Tables\Columns\TextColumn::make('parent.name')
                    ->sortable()
                    ->label(__('accounts.parent')),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('accounts.opening_balance')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('accounts.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('accounts.is_active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'asset' => __('accounts.type_options.asset'),
                        'liability' => __('accounts.type_options.liability'),
                        'equity' => __('accounts.type_options.equity'),
                        'revenue' => __('accounts.type_options.revenue'),
                        'expense' => __('accounts.type_options.expense'),
                    ])
                    ->label(__('accounts.type')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('accounts.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('accounts.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions(static::getExportActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}

