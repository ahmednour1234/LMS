<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueRecognition extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'recognized_amount',
        'recognized_at',
        'journal_id',
    ];

    protected function casts(): array
    {
        return [
            'recognized_amount' => 'decimal:2',
            'recognized_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Enrollment\Models\Enrollment::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}

