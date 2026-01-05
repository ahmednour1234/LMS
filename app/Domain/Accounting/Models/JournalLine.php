<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit',
        'credit',
        'memo',
        'user_id',
        'enrollment_id',
        'cost_center_id',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Enrollment\Models\Enrollment::class);
    }
}

