<?php

namespace App\Domain\Training\Models;

use App\Domain\Enrollment\Models\Student;
use App\Domain\Media\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'student_id',
        'submission_text',
        'media_file_id',
        'score',
        'feedback',
        'reviewed_at',
        'reviewed_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'feedback' => 'array',
            'score' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

