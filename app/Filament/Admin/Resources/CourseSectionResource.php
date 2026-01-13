<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSection;
use App\Filament\Admin\Resources\CourseSectionResource\Pages;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CourseSectionResource extends Resource
{
    protected static ?string $model = CourseSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'training';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('navigation.course_sections');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.course_sections');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.course_sections');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    /**
     * ✅ Safe query for courses filtered by current user's branch when possible.
     */
    protected static function coursesQueryForUserBranch(): Builder
    {
        $query = Course::query()->orderBy('code');

        $user = Auth::user();
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        $branchId = $user->branch_id;

        // Prefer courses.branch_id if exists
        if ($branchId && Schema::hasColumn('courses', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('course_id')
                ->label(__('course_sections.course'))
                ->options(fn () => self::coursesQueryForUserBranch()->pluck('code', 'id')->toArray())
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('title.ar')
                ->label(__('course_sections.title_ar'))
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('title.en')
                ->label(__('course_sections.title_en'))
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description.ar')
                ->label(__('course_sections.description_ar'))
                ->rows(3),

            Forms\Components\Textarea::make('description.en')
                ->label(__('course_sections.description_en'))
                ->rows(3),

            Forms\Components\TextInput::make('order')
                ->numeric()
                ->default(0)
                ->label(__('course_sections.order')),

            Forms\Components\Toggle::make('is_active')
                ->label(__('course_sections.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                // eager load to avoid N+1
                $query->with(['course']);

                if (!$user || $user->isSuperAdmin()) {
                    return $query;
                }

                $branchId = $user->branch_id;

                // ✅ Safe filter: course_sections.branch_id (if exists)
                if (Schema::hasColumn('course_sections', 'branch_id')) {
                    return $query->where('branch_id', $branchId);
                }

                // ✅ Safe filter: courses.branch_id through relation
                if (Schema::hasColumn('courses', 'branch_id')) {
                    return $query->whereHas('course', fn (Builder $q) => $q->where('branch_id', $branchId));
                }

                // Otherwise: no filter (avoid SQL errors)
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->sortable()
                    ->label(__('course_sections.course')),

                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->searchable()
                    ->sortable()
                    ->label(__('course_sections.title')),

                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label(__('course_sections.order')),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('course_sections.is_active')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label(__('course_sections.course'))
                    ->options(fn () => self::coursesQueryForUserBranch()->pluck('code', 'id')->toArray()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('course_sections.is_active')),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCourseSections::route('/'),
            'create' => Pages\CreateCourseSection::route('/create'),
            'view'   => Pages\ViewCourseSection::route('/{record}'),
            'edit'   => Pages\EditCourseSection::route('/{record}/edit'),
        ];
    }
}
