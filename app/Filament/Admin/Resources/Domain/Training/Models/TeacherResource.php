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
        return __('teachers.teachers');
    }

    public static function getModelLabel(): string
    {
        return __('teachers.teacher');
    }

    public static function getPluralModelLabel(): string
    {
        return __('teachers.teachers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('teachers.name')),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label(__('teachers.email')),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateTeacher)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? \Hash::make($state) : null)
                    ->maxLength(255)
                    ->label(__('teachers.password'))
                    ->helperText(fn ($livewire) => $livewire instanceof Pages\EditTeacher ? __('teachers.password_helper') : null),
                Forms\Components\Select::make('sex')
                    ->options([
                        'male' => __('teachers.sex_options.male'),
                        'female' => __('teachers.sex_options.female'),
                        'other' => __('teachers.sex_options.other'),
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
                    ->label(__('teachers.name')),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label(__('teachers.email')),
                Tables\Columns\TextColumn::make('sex')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('teachers.sex_options.' . $state))
                    ->label(__('teachers.sex'))
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('photo')
                    ->label(__('teachers.photo'))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable()
                    ->label(__('teachers.active')),
                Tables\Columns\TextColumn::make('owned_courses_count')
                    ->counts('ownedCourses')
                    ->label(__('teachers.owned_courses'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_courses_count')
                    ->counts('assignedCourses')
                    ->label(__('teachers.assigned_courses'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sex')
                    ->options([
                        'male' => __('teachers.sex_options.male'),
                        'female' => __('teachers.sex_options.female'),
                        'other' => __('teachers.sex_options.other'),
                    ])
                    ->label(__('teachers.sex')),
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('teachers.active')),
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
