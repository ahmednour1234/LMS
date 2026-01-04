<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Student;
use App\Filament\Admin\Resources\StudentResource\Pages;
use App\Filament\Concerns\HasTableExports;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    use HasTableExports;

    protected static ?string $model = Student::class;

    protected static ?string $slug = 'students';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'enrollment';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('navigation.students');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.student');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.students');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.enrollment');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label(__('students.user')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('students.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\TextInput::make('student_code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label(__('students.student_code')),
                Forms\Components\TextInput::make('national_id')
                    ->maxLength(255)
                    ->label(__('students.national_id')),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255)
                    ->label(__('students.phone')),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => __('students.status_options.active'),
                        'inactive' => __('students.status_options.inactive'),
                        'suspended' => __('students.status_options.suspended'),
                    ])
                    ->default('active')
                    ->required()
                    ->label(__('students.status')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->visibleTo(auth()->user(), 'students');
            })
            ->columns([
                Tables\Columns\TextColumn::make('student_code')
                    ->searchable()
                    ->sortable()
                    ->label(__('students.student_code')),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('students.user')),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('students.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('students.status_options.' . $state))
                    ->color(function (string $state): string {
                        return match ($state) {
                            'active' => 'success',
                            'inactive' => 'gray',
                            'suspended' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->label(__('students.status')),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label(__('students.phone'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => __('students.status_options.active'),
                        'inactive' => __('students.status_options.inactive'),
                        'suspended' => __('students.status_options.suspended'),
                    ])
                    ->label(__('students.status')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('students.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}

