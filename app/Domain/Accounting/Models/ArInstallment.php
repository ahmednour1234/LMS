<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ar_invoice_id',
        'installment_no',
        'due_date',
        'amount',
        'status',
        'paid_amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'status' => 'string',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function arInvoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'installment_id');
    }
}

