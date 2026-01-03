<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Course;
use App\Filament\Admin\Resources\CourseResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Courses';

    protected static ?string $modelLabel = 'Course';

    protected static ?string $pluralModelLabel = 'Courses';

    protected static ?string $navigationGroup = 'Training Catalog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('program_id')
                    ->relationship('program', 'code')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('courses.program')),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('courses.code')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('courses.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->label(__('courses.price')),
                Forms\Components\Toggle::make('is_installment_enabled')
                    ->label(__('courses.is_installment_enabled'))
                    ->default(false),
                Forms\Components\Select::make('trainers')
                    ->relationship('trainers', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label(__('courses.trainers'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('courses.is_active'))
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
                    ->label(__('courses.code')),
                Tables\Columns\TextColumn::make('program.code')
                    ->sortable()
                    ->label(__('courses.program')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('courses.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable()
                    ->label(__('courses.price')),
                Tables\Columns\IconColumn::make('is_installment_enabled')
                    ->boolean()
                    ->label(__('courses.is_installment_enabled')),
                Tables\Columns\TextColumn::make('trainers_count')
                    ->counts('trainers')
                    ->label(__('courses.trainers_count')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('courses.is_active')),
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
                Tables\Filters\SelectFilter::make('program_id')
                    ->relationship('program', 'code')
                    ->label(__('courses.program')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('courses.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\TernaryFilter::make('is_installment_enabled')
                    ->label(__('courses.is_installment_enabled')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('courses.is_active')),
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
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
