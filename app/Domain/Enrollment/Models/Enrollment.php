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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    use HasFactory, HasVisibilityScope;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($enrollment) {
            if (empty($enrollment->reference)) {
                $enrollment->reference = static::generateReference();
            }
        });
    }

    protected static function generateReference(): string
    {
        $year = now()->year;
        $lastEnrollment = static::where('reference', 'like', "ENR-{$year}-%")
            ->orderBy('reference', 'desc')
            ->first();

        if ($lastEnrollment && preg_match('/ENR-' . $year . '-(\d+)/', $lastEnrollment->reference, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('ENR-%s-%06d', $year, $nextNumber);
    }

    protected $fillable = [
        'reference',
        'student_id',
        'user_id',
        'course_id',
        'status',
        'pricing_type',
        'total_amount',
        'progress_percent',
        'enrolled_at',
        'registered_at',
        'started_at',
        'completed_at',
        'notes',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'pricing_type' => 'string',
            'total_amount' => 'decimal:2',
            'progress_percent' => 'decimal:2',
            'enrolled_at' => 'datetime',
            'registered_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function arInvoice(): HasOne
    {
        return $this->hasOne(\App\Domain\Accounting\Models\ArInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Domain\Accounting\Models\Payment::class);
    }

    public function revenueRecognitions(): HasMany
    {
        return $this->hasMany(\App\Domain\Accounting\Models\RevenueRecognition::class);
    }
}

