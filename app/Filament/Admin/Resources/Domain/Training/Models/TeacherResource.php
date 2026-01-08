<?php

namespace App\Filament\Admin\Resources\Domain\Training\Models;

use App\Domain\Training\Models\Teacher;
use App\Filament\Admin\Resources\Domain\Training\Models\TeacherResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $slug = 'teachers';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Teachers';
    }

    public static function getModelLabel(): string
    {
        return __('teachers.teacher');
    }

    public static function getPluralModelLabel(): string
    {
        return 'Teachers';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255)
                    ->label(__('teachers.name')),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label(__('teachers.email')),
                Forms\Components\Select::make('sex')
                    ->options([
                        'male' => __('teachers.sex_options.male'),
                        'female' => __('teachers.sex_options.female'),
                    ])
                    ->nullable()
                    ->label(__('teachers.sex')),
                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->directory('teachers')
                    ->visibility('public')
                    ->nullable()
                    ->label(__('teachers.photo')),
                Forms\Components\Toggle::make('active')
                    ->label(__('teachers.active'))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('teachers.name')),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label(__('teachers.email')),
                Tables\Columns\TextColumn::make('sex')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __('teachers.sex_options.' . $state) : '-')
                    ->label(__('teachers.sex')),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable()
                    ->label(__('teachers.active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sex')
                    ->options([
                        'male' => __('teachers.sex_options.male'),
                        'female' => __('teachers.sex_options.female'),
                    ])
                    ->label(__('teachers.sex')),
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('teachers.active')),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn ($record) => $record->active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['active' => !$record->active]);
                    }),
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'view' => Pages\ViewTeacher::route('/{record}'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
