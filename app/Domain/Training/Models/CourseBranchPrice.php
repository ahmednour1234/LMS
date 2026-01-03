<?php

namespace App\Domain\Training\Models;

use App\Domain\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseBranchPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'branch_id',
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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

