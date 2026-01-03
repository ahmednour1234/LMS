<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Media\Models\MediaFile;
use App\Filament\Admin\Resources\MediaFileResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MediaFileResource extends Resource
{
    protected static ?string $model = MediaFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'navigation.media_files';

    protected static ?string $pluralModelLabel = 'navigation.media_files';

    public static function getNavigationLabel(): string
    {
        return __('navigation.media_files');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->disk('local')
                    ->directory('media')
                    ->visibility('private')
                    ->label(__('media_files.path')),
                Forms\Components\TextInput::make('filename')
                    ->maxLength(255)
                    ->label(__('media_files.filename')),
                Forms\Components\TextInput::make('original_filename')
                    ->maxLength(255)
                    ->label(__('media_files.original_filename')),
                Forms\Components\TextInput::make('mime_type')
                    ->maxLength(255)
                    ->label(__('media_files.mime_type')),
                Forms\Components\TextInput::make('size')
                    ->numeric()
                    ->label(__('media_files.size')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('media_files.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
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
                Tables\Columns\TextColumn::make('filename')
                    ->searchable()
                    ->sortable()
                    ->label(__('media_files.filename')),
                Tables\Columns\TextColumn::make('original_filename')
                    ->searchable()
                    ->label(__('media_files.original_filename')),
                Tables\Columns\TextColumn::make('mime_type')
                    ->searchable()
                    ->label(__('media_files.mime_type')),
                Tables\Columns\TextColumn::make('size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2).' KB' : '-')
                    ->sortable()
                    ->label(__('media_files.size')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('media_files.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->label(__('media_files.user')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('media_files.branch'))
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
            'index' => Pages\ListMediaFiles::route('/'),
            'create' => Pages\CreateMediaFile::route('/create'),
            'edit' => Pages\EditMediaFile::route('/{record}/edit'),
        ];
    }
}

