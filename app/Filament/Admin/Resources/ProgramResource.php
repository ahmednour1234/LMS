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

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('navigation.programs');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.programs');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.programs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'code', fn (Builder $query) => $query->where('id', '!=', $form->getRecord()?->id))
                    ->searchable()
                    ->preload()
                    ->label(__('programs.parent'))
                    ->helperText(__('programs.parent_helper')),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                        $branchId = auth()->user()->isSuperAdmin() ? $get('branch_id') : auth()->user()->branch_id;
                        return $rule->where('branch_id', $branchId);
                    })
                    ->label(__('programs.code')),
                Forms\Components\TextInput::make('name.ar')
                    ->label(__('programs.name_ar'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name.en')
                    ->label(__('programs.name_en'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description.ar')
                    ->label(__('programs.description_ar'))
                    ->rows(3),
                Forms\Components\Textarea::make('description.en')
                    ->label(__('programs.description_en'))
                    ->rows(3),
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
                Tables\Columns\TextColumn::make('name')
                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                    ->searchable()
                    ->sortable()
                    ->label(__('programs.name')),
                Tables\Columns\TextColumn::make('parent.code')
                    ->sortable()
                    ->label(__('programs.parent'))
                    ->placeholder('-'),
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
                Tables\Filters\SelectFilter::make('parent_id')
                    ->relationship('parent', 'code')
                    ->label(__('programs.parent')),
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
