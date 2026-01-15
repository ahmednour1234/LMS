<?php

namespace App\Domain\Training\Models;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Enums\AttendanceMethod;
use App\Domain\Training\Enums\AttendanceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSessionAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'enrollment_id',
        'status',
        'method',
        'marked_by',
        'marked_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'method' => AttendanceMethod::class,
            'marked_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
