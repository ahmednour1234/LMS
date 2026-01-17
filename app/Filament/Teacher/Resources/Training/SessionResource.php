<?php

namespace App\Filament\Teacher\Resources\Training;

use App\Domain\Training\Enums\SessionLocationType;
use App\Domain\Training\Enums\SessionProvider;
use App\Domain\Training\Enums\SessionStatus;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSession;
use App\Filament\Teacher\Resources\Training\SessionResource\Pages;
use App\Http\Services\CourseSessionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class SessionResource extends Resource
{
    protected static ?string $model = CourseSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('attendance.sessions');
    }

    public static function getModelLabel(): string
    {
        return __('attendance.session');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attendance.sessions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.training');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('teacher_id', auth('teacher')->id());
    }

    public static function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();
        
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => $query->where('owner_teacher_id', $teacherId))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('attendance.course'))
                    ->live(),
                Forms\Components\Select::make('lesson_id')
                    ->relationship('lesson', 'id', function (Builder $query, Forms\Get $get) use ($teacherId) {
                        $courseId = $get('course_id');
                        if ($courseId) {
                            $query->whereHas('section', fn ($q) => $q->where('course_id', $courseId)->whereHas('course', fn ($c) => $c->where('owner_teacher_id', $teacherId)));
                        }
                        return $query->orderBy('id');
                    })
                    ->getOptionLabelUsing(function ($record) {
                        if (is_object($record) && isset($record->title)) {
                            return \App\Support\Helpers\MultilingualHelper::formatMultilingualField($record->title) ?: 'N/A';
                        }
                        return 'N/A';
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label(__('attendance.lesson')),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->label(__('attendance.title')),
                Forms\Components\Select::make('location_type')
                    ->options([
                        SessionLocationType::ONLINE->value => __('attendance.location_type_options.online'),
                        SessionLocationType::ONSITE->value => __('attendance.location_type_options.onsite'),
                    ])
                    ->required()
                    ->default(SessionLocationType::ONLINE->value)
                    ->label(__('attendance.location_type'))
                    ->live(),
                Forms\Components\Select::make('provider')
                    ->options([
                        SessionProvider::JITSI->value => 'Jitsi',
                    ])
                    ->nullable()
                    ->label(__('attendance.provider'))
                    ->visible(fn (Forms\Get $get) => $get('location_type') === SessionLocationType::ONLINE->value),
                Forms\Components\TextInput::make('room_slug')
                    ->disabled()
                    ->dehydrated(false)
                    ->label(__('attendance.room_slug'))
                    ->visible(fn (Forms\Get $get) => $get('provider') === SessionProvider::JITSI->value),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->required()
                    ->label(__('attendance.starts_at')),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->required()
                    ->after('starts_at')
                    ->label(__('attendance.ends_at')),
                Forms\Components\Select::make('status')
                    ->options([
                        SessionStatus::SCHEDULED->value => __('attendance.status_options.scheduled'),
                        SessionStatus::COMPLETED->value => __('attendance.status_options.completed'),
                        SessionStatus::CANCELLED->value => __('attendance.status_options.cancelled'),
                    ])
                    ->required()
                    ->default(SessionStatus::SCHEDULED->value)
                    ->label(__('attendance.status')),
                Forms\Components\TextInput::make('onsite_qr_secret')
                    ->hidden()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->sortable()
                    ->label(__('attendance.course')),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->label(__('attendance.title')),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('attendance.starts_at')),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('attendance.ends_at')),
                Tables\Columns\TextColumn::make('location_type')
                    ->formatStateUsing(fn ($state) => $state instanceof SessionLocationType 
                        ? __('attendance.location_type_options.' . $state->value)
                        : __('attendance.location_type_options.' . $state))
                    ->badge()
                    ->label(__('attendance.location_type')),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state instanceof SessionStatus
                        ? __('attendance.status_options.' . $state->value)
                        : __('attendance.status_options.' . $state))
                    ->badge()
                    ->label(__('attendance.status')),
                Tables\Columns\TextColumn::make('room_slug')
                    ->label(__('attendance.room_slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'code', fn (Builder $query) => $query->where('owner_teacher_id', auth('teacher')->id()))
                    ->label(__('attendance.course')),
                Tables\Filters\SelectFilter::make('location_type')
                    ->options([
                        SessionLocationType::ONLINE->value => __('attendance.location_type_options.online'),
                        SessionLocationType::ONSITE->value => __('attendance.location_type_options.onsite'),
                    ])
                    ->label(__('attendance.location_type')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SessionStatus::SCHEDULED->value => __('attendance.status_options.scheduled'),
                        SessionStatus::COMPLETED->value => __('attendance.status_options.completed'),
                        SessionStatus::CANCELLED->value => __('attendance.status_options.cancelled'),
                    ])
                    ->label(__('attendance.status')),
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
            'index' => Pages\ListSessions::route('/'),
            'create' => Pages\CreateSession::route('/create'),
            'edit' => Pages\EditSession::route('/{record}/edit'),
        ];
    }
}
