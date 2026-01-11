<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Branch\Models\Branch;
use App\Domain\Accounting\Services\VoucherNumberService;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_no',
        'voucher_type',
        'voucher_date',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'payee_name',
        'reference_no',
        'description',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'voucher_type' => VoucherType::class,
            'voucher_date' => 'date',
            'status' => VoucherStatus::class,
            'exchange_rate' => 'decimal:6',
            'approved_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->voucher_no)) {
                $voucher->voucher_no = app(VoucherNumberService::class)->generateNextNumber($voucher->voucher_type);
            }
            if (empty($voucher->created_by)) {
                $voucher->created_by = auth()->id();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function voucherLines(): HasMany
    {
        return $this->hasMany(VoucherLine::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'voucher_id');
    }

    public function isDraft(): bool
    {
        return $this->status === VoucherStatus::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === VoucherStatus::POSTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === VoucherStatus::CANCELLED;
    }

    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->voucherLines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->voucherLines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        $debit = $this->total_debit;
        $credit = $this->total_credit;
        return abs($debit - $credit) < 0.01;
    }
}
