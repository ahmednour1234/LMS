<?php

namespace App\Filament\Teacher\Resources\Training\SessionResource\Pages;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\CourseSessionAttendance;
use App\Filament\Teacher\Resources\Training\SessionResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditSession extends EditRecord
{
    protected static string $resource = SessionResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->teacher_id !== auth('teacher')->id()) {
            abort(404);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_attendance')
                ->label(__('attendance.mark_attendance'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->form([
                    Forms\Components\Section::make(__('attendance.mark_attendance'))
                        ->description(__('attendance.select_students_attended'))
                        ->schema([
                            Forms\Components\CheckboxList::make('enrollments')
                                ->label(__('attendance.students'))
                                ->options(function () {
                                    $enrollments = Enrollment::where('course_id', $this->record->course_id)
                                        ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
                                        ->with('student')
                                        ->get();
                                    
                                    return $enrollments->mapWithKeys(function ($enrollment) {
                                        $studentName = $enrollment->student->name ?? 'N/A';
                                        return [$enrollment->id => $studentName];
                                    })->toArray();
                                })
                                ->default(function () {
                                    return CourseSessionAttendance::where('session_id', $this->record->id)
                                        ->where('status', \App\Domain\Training\Enums\AttendanceStatus::PRESENT)
                                        ->pluck('enrollment_id')
                                        ->toArray();
                                })
                                ->columns(2)
                                ->searchable(),
                        ]),
                ])
                ->action(function (array $data) {
                    $sessionId = $this->record->id;
                    $teacherId = auth('teacher')->id();
                    $markedAt = now();
                    
                    $selectedEnrollments = $data['enrollments'] ?? [];
                    
                    $existingAttendances = CourseSessionAttendance::where('session_id', $sessionId)
                        ->pluck('enrollment_id')
                        ->toArray();
                    
                    $toAdd = array_diff($selectedEnrollments, $existingAttendances);
                    $toRemove = array_diff($existingAttendances, $selectedEnrollments);
                    
                    if (!empty($toAdd)) {
                        $records = [];
                        foreach ($toAdd as $enrollmentId) {
                            $records[] = [
                                'session_id' => $sessionId,
                                'enrollment_id' => $enrollmentId,
                                'status' => \App\Domain\Training\Enums\AttendanceStatus::PRESENT->value,
                                'method' => \App\Domain\Training\Enums\AttendanceMethod::MANUAL->value,
                                'marked_by' => $teacherId,
                                'marked_at' => $markedAt,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        DB::table('course_session_attendances')->insert($records);
                    }
                    
                    if (!empty($toRemove)) {
                        CourseSessionAttendance::where('session_id', $sessionId)
                            ->whereIn('enrollment_id', $toRemove)
                            ->delete();
                    }
                    
                    CourseSessionAttendance::where('session_id', $sessionId)
                        ->whereIn('enrollment_id', $selectedEnrollments)
                        ->update([
                            'status' => \App\Domain\Training\Enums\AttendanceStatus::PRESENT->value,
                            'method' => \App\Domain\Training\Enums\AttendanceMethod::MANUAL->value,
                            'marked_by' => $teacherId,
                            'marked_at' => $markedAt,
                            'updated_at' => now(),
                        ]);
                    
                    Notification::make()
                        ->title(__('attendance.attendance_marked'))
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
