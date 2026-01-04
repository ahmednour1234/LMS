<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Branch\Models\Branch;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'settings';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('navigation.users');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.users');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('users.name')),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label(__('users.email')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label(__('users.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label(__('users.roles')),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof \App\Filament\Admin\Resources\UserResource\Pages\CreateUser)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->label(__('users.password')),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('users.name')),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label(__('users.email')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('users.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label(__('users.roles')),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('users.email_verified_at')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('users.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
