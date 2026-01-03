<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Branch\Models\Branch;
use App\Enums\JournalStatus;
use App\Models\User;
use App\Support\Traits\HasVisibilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory, HasVisibilityScope;

    protected $fillable = [
        'reference',
        'reference_type',
        'reference_id',
        'date',
        'description',
        'status',
        'branch_id',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => JournalStatus::class,
            'posted_at' => 'datetime',
        ];
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

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

}

