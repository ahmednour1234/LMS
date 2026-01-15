<?php

namespace App\Domain\Training\Models;

use App\Domain\Training\Enums\LocationType;
use App\Domain\Training\Enums\SessionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'teacher_id',
        'title',
        'location_type',
        'provider',
        'room_slug',
        'starts_at',
        'ends_at',
        'status',
        'onsite_qr_secret',
    ];

    protected function casts(): array
    {
        return [
            'location_type' => LocationType::class,
            'status' => SessionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseSessionAttendance::class, 'session_id');
    }
}
