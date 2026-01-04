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
        return __('Teachers');
    }

    public static function getModelLabel(): string
    {
        return __('Teacher');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Teachers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Training');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('Name')),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label(__('Email')),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateTeacher)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? \Hash::make($state) : null)
                    ->maxLength(255)
                    ->label(__('Password'))
                    ->helperText(fn ($livewire) => $livewire instanceof Pages\EditTeacher ? __('Leave empty to keep current password') : null),
                Forms\Components\Select::make('sex')
                    ->options([
                        'male' => __('Male'),
                        'female' => __('Female'),
                        'other' => __('Other'),
                    ])
                    ->nullable()
                    ->label(__('Sex')),
                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->directory('teachers')
                    ->visibility('public')
                    ->nullable()
                    ->label(__('Photo')),
                Forms\Components\Toggle::make('active')
                    ->label(__('Active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('Name')),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label(__('Email')),
                Tables\Columns\TextColumn::make('sex')
                    ->badge()
                    ->label(__('Sex'))
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('photo')
                    ->label(__('Photo'))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable()
                    ->label(__('Active')),
                Tables\Columns\TextColumn::make('owned_courses_count')
                    ->counts('ownedCourses')
                    ->label(__('Owned Courses'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_courses_count')
                    ->counts('assignedCourses')
                    ->label(__('Assigned Courses'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sex')
                    ->options([
                        'male' => __('Male'),
                        'female' => __('Female'),
                        'other' => __('Other'),
                    ])
                    ->label(__('Sex')),
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('Active')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
