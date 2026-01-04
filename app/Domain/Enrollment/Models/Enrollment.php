<?php

namespace App\Domain\Enrollment\Models;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Models\Course;
use App\Enums\EnrollmentStatus;
use App\Models\User;
use App\Support\Traits\HasVisibilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory, HasVisibilityScope;

    protected $fillable = [
        'reference',
        'student_id',
        'course_id',
        'status',
        'enrolled_at',
        'registered_at',
        'notes',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

