<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Training\Enums\LocationType;
use App\Domain\Training\Enums\SessionStatus;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSession;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Teacher;
use App\Filament\Admin\Resources\CourseSessionResource\Pages;
use App\Http\Services\AttendanceService;
use App\Http\Services\CourseSessionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class CourseSessionResource extends Resource
{
    protected static ?string $model = CourseSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'training';

    protected static ?int $navigationSort = 3;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'code')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('attendance.course')),
                Forms\Components\Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label(__('attendance.lesson')),
                Forms\Components\Select::make('teacher_id')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label(__('attendance.teacher')),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->label(__('attendance.title')),
                Forms\Components\Select::make('location_type')
                    ->options([
                        LocationType::ONLINE->value => __('attendance.location_type_options.online'),
                        LocationType::ONSITE->value => __('attendance.location_type_options.onsite'),
                    ])
                    ->required()
                    ->default(LocationType::ONLINE->value)
                    ->label(__('attendance.location_type'))
                    ->live(),
                Forms\Components\Select::make('provider')
                    ->options([
                        'jitsi' => 'Jitsi',
                    ])
                    ->nullable()
                    ->label(__('attendance.provider'))
                    ->visible(fn (Forms\Get $get) => $get('location_type') === LocationType::ONLINE->value),
                Forms\Components\TextInput::make('room_slug')
                    ->disabled()
                    ->dehydrated(false)
                    ->label(__('attendance.room_slug'))
                    ->visible(fn (Forms\Get $get) => $get('provider') === 'jitsi'),
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
                    ->formatStateUsing(fn ($state) => $state instanceof LocationType 
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
                    ->relationship('course', 'code')
                    ->label(__('attendance.course')),
                Tables\Filters\SelectFilter::make('location_type')
                    ->options([
                        LocationType::ONLINE->value => __('attendance.location_type_options.online'),
                        LocationType::ONSITE->value => __('attendance.location_type_options.onsite'),
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
                Tables\Actions\Action::make('mark_attendance')
                    ->label(__('attendance.mark_attendance'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form(function (CourseSession $record) {
                        $enrollments = \App\Domain\Enrollment\Models\Enrollment::where('course_id', $record->course_id)
                            ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
                            ->with('student')
                            ->get();

                        $fields = [];
                        foreach ($enrollments as $enrollment) {
                            $existing = \App\Domain\Training\Models\CourseSessionAttendance::where('session_id', $record->id)
                                ->where('enrollment_id', $enrollment->id)
                                ->first();

                            $fields[] = Forms\Components\Select::make("attendance.{$enrollment->id}")
                                ->label($enrollment->student->name ?? "Enrollment #{$enrollment->id}")
                                ->options([
                                    'present' => __('attendance.status_options.present'),
                                    'absent' => __('attendance.status_options.absent'),
                                    'late' => __('attendance.status_options.late'),
                                    'excused' => __('attendance.status_options.excused'),
                                ])
                                ->default($existing ? $existing->status->value : 'absent')
                                ->required();
                        }

                        return $fields;
                    })
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('mark_all_present')
                            ->label(__('attendance.mark_all_present'))
                            ->color('success')
                            ->action(function (CourseSession $record, Forms\Set $set) {
                                $enrollments = \App\Domain\Enrollment\Models\Enrollment::where('course_id', $record->course_id)
                                    ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
                                    ->pluck('id');
                                
                                foreach ($enrollments as $enrollmentId) {
                                    $set("attendance.{$enrollmentId}", 'present');
                                }
                            }),
                        Tables\Actions\Action::make('mark_all_absent')
                            ->label(__('attendance.mark_all_absent'))
                            ->color('danger')
                            ->action(function (CourseSession $record, Forms\Set $set) {
                                $enrollments = \App\Domain\Enrollment\Models\Enrollment::where('course_id', $record->course_id)
                                    ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
                                    ->pluck('id');
                                
                                foreach ($enrollments as $enrollmentId) {
                                    $set("attendance.{$enrollmentId}", 'absent');
                                }
                            }),
                    ])
                    ->action(function (CourseSession $record, array $data) {
                        $attendanceService = App::make(AttendanceService::class);
                        $attendanceData = $data['attendance'] ?? [];
                        $attendanceService->markAttendance($record->id, $attendanceData);
                        \Filament\Notifications\Notification::make()
                            ->title(__('attendance.attendance_marked'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (CourseSession $record) => $record->status === SessionStatus::SCHEDULED || $record->status === SessionStatus::COMPLETED),
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
            'index' => Pages\ListCourseSessions::route('/'),
            'create' => Pages\CreateCourseSession::route('/create'),
            'view' => Pages\ViewCourseSession::route('/{record}'),
            'edit' => Pages\EditCourseSession::route('/{record}/edit'),
        ];
    }
}
