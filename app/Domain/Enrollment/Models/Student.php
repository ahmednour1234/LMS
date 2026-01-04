<?php

namespace App\Domain\Enrollment\Models;

use App\Domain\Branch\Models\Branch;
use App\Models\User;
use App\Support\Traits\HasVisibilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, HasVisibilityScope, SoftDeletes;

    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'student_code',
        'national_id',
        'phone',
        'email',
        'password',
        'sex',
        'photo',
        'status',
        'active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}

