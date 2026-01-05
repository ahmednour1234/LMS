<?php

namespace App\Models;

use App\Domain\Enrollment\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'email',
        'code',
        'purpose',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope a query to only include valid (not expired) OTP codes.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include non-consumed OTP codes.
     */
    public function scopeNotConsumed(Builder $query): Builder
    {
        return $query->whereNull('consumed_at');
    }

    /**
     * Scope a query to filter by purpose.
     */
    public function scopeByPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Check if the OTP code is valid (not expired and not consumed).
     */
    public function isValid(): bool
    {
        return $this->expires_at > now() && $this->consumed_at === null;
    }

    /**
     * Check if the OTP code has exceeded max attempts.
     */
    public function hasExceededMaxAttempts(int $maxAttempts = 3): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    /**
     * Increment the attempts counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark the OTP code as consumed.
     */
    public function consume(): void
    {
        $this->update(['consumed_at' => now()]);
    }
}

