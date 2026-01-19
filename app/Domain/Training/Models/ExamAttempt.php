<?php

namespace App\Domain\Training\Models;

use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'enrollment_id',
        'attempt_no',
        'score',
        'max_score',
        'percentage',
        'status',
        'started_at',
        'submitted_at',
        'graded_at',
        'graded_by_teacher_id',
    ];

    protected function casts(): array
    {
        return [
            'attempt_no' => 'integer',
            'score' => 'integer',
            'max_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'status' => 'string',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Enrollment\Models\Enrollment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'attempt_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'graded_by_teacher_id');
    }
}
