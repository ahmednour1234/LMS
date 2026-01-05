<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Section;
use App\Filament\Admin\Resources\SectionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Sections');
    }

    public static function getModelLabel(): string
    {
        return __('Section');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Sections');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Training');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['super-admin', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => $query->where('branch_id', auth()->user()->branch_id ?? null))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('Course')),
                Forms\Components\TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('Description'))
                    ->rows(3),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->label(__('Sort Order')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('course', fn ($q) => $q->where('branch_id', $user->branch_id));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->sortable()
                    ->label(__('Course')),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->label(__('Title')),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label(__('Sort Order')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('Is Active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'code')
                    ->label(__('Course')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Is Active')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'view' => Pages\ViewSection::route('/{record}'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
        ];
    }
}

