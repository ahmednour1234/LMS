<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Branch\Models\Branch;
use App\Models\User;
use App\Support\Traits\HasVisibilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArInvoice extends Model
{
    use HasFactory, HasVisibilityScope;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'amount',
        'tax_amount',
        'total_amount',
        'due_date',
        'paid_at',
        'notes',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
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
}

