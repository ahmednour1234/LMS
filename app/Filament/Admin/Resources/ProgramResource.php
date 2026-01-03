<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Program;
use App\Filament\Admin\Resources\ProgramResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Programs';

    protected static ?string $modelLabel = 'Program';

    protected static ?string $pluralModelLabel = 'Programs';

    protected static ?string $navigationGroup = 'Training Catalog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('programs.code')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('programs.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('programs.is_active'))
                    ->default(true),
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
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label(__('programs.code')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('programs.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('courses_count')
                    ->counts('courses')
                    ->label(__('programs.courses_count')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('programs.is_active')),
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
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('programs.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('programs.is_active')),
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
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
