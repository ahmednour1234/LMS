<?php

namespace App\Domain\Training\Models;

use App\Domain\Branch\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'branch_id',
        'code',
        'price',
        'is_installment_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_installment_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_trainer');
    }

    public function branchPrices(): HasMany
    {
        return $this->hasMany(CourseBranchPrice::class);
    }
}

