<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Branch\Models\Branch;
use App\Models\User;
use App\Support\Traits\HasVisibilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArInvoice extends Model
{
    use HasFactory, HasVisibilityScope;

    protected $fillable = [
        'enrollment_id',
        'user_id',
        'branch_id',
        'total_amount',
        'due_amount',
        'status',
        'issued_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'status' => 'string',
            'issued_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Enrollment\Models\Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function arInstallments(): HasMany
    {
        return $this->hasMany(ArInstallment::class);
    }
}

