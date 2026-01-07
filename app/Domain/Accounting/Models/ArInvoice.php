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
        'subtotal',
        'manual_discount',
        'manual_discount_reason',
        'manual_discount_by',
        'manual_discount_at',
        'promo_code_id',
        'promo_discount',
        'tax_rate',
        'tax_total',
        'status',
        'issued_at',
        'created_by',
        'updated_by',
    ];

    protected $guarded = [
        'due_amount', // Computed field - cannot be mass assigned
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:3',
            // Note: due_amount is computed via accessor, cast kept for backward compatibility but value is ignored
            'due_amount' => 'decimal:3',
            'subtotal' => 'decimal:3',
            'manual_discount' => 'decimal:3',
            'promo_discount' => 'decimal:3',
            'tax_rate' => 'decimal:2',
            'tax_total' => 'decimal:3',
            'status' => 'string',
            'issued_at' => 'datetime',
            'manual_discount_at' => 'datetime',
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

    /**
     * Calculate paid amount from payments directly by enrollment_id
     * Formula: SUM(payments.amount WHERE status='paid' AND enrollment_id = ar_invoices.enrollment_id)
     */
    public function getPaidAmountAttribute(): float
    {
        return (float) \App\Domain\Accounting\Models\Payment::where('enrollment_id', $this->enrollment_id)
            ->where('status', 'paid')
            ->sum('amount') ?? 0;
    }

    /**
     * Calculate due amount as total_amount - paid_amount
     * Formula: total_amount - SUM(payments.amount WHERE status='paid' AND enrollment_id = ar_invoices.enrollment_id)
     * This is always computed and ignores the stored database value
     */
    public function getDueAmountAttribute(): float
    {
        $paidAmount = $this->getPaidAmountAttribute();
        return max(0, $this->total_amount - $paidAmount);
    }

    /**
     * Update invoice status based on computed due_amount
     * Status logic based on computed due_amount:
     * - due_amount == total_amount -> 'open'
     * - 0 < due_amount < total_amount -> 'partial'
     * - due_amount == 0 -> 'paid'
     */
    public function updateStatus(): void
    {
        $dueAmount = $this->due_amount; // Uses computed accessor

        if ($dueAmount == $this->total_amount) {
            $status = 'open';
        } elseif ($dueAmount > 0 && $dueAmount < $this->total_amount) {
            $status = 'partial';
        } else { // dueAmount == 0
            $status = 'paid';
        }

        if ($this->status !== $status) {
            $this->status = $status;
            $this->save();
        }
    }

    /**
     * Boot method to register model events
     */
    protected static function boot(): void
    {
        parent::boot();

        // Prevent direct updates to due_amount (but allow during creation)
        static::updating(function ($invoice) {
            // Check if due_amount is in the dirty attributes
            if (array_key_exists('due_amount', $invoice->getDirty())) {
                throw new \Illuminate\Database\Eloquent\MassAssignmentException(
                    'due_amount is computed and cannot be updated directly. It is calculated as: total_amount - SUM(payments.amount WHERE status=\'paid\' AND enrollment_id matches)'
                );
            }
        });
    }
}

