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
        'journal_date',
        'description',
        'status',
        'branch_id',
        'posted_at',
        'posted_by',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'original_status' => null,
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'status' => JournalStatus::class,
            'posted_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // Store original status when model is retrieved
        static::retrieved(function ($journal) {
            $journal->attributes['original_status'] = $journal->status;
        });

        // Prevent updating if originally posted
        static::updating(function ($journal) {
            $originalStatus = $journal->attributes['original_status'] ?? $journal->getOriginal('status');
            
            // Normalize status value for comparison
            $originalValue = $originalStatus instanceof JournalStatus ? $originalStatus : JournalStatus::tryFrom($originalStatus ?? '');
            $currentOriginal = $journal->getOriginal('status');
            $currentOriginalValue = $currentOriginal instanceof JournalStatus ? $currentOriginal : JournalStatus::tryFrom($currentOriginal ?? '');
            
            // Check if it was posted when retrieved or in database
            if ($originalValue === JournalStatus::POSTED || $currentOriginalValue === JournalStatus::POSTED) {
                throw new \RuntimeException(
                    'Cannot update a posted journal. Posted journals are immutable.'
                );
            }
        });

        // Prevent deleting if posted
        static::deleting(function ($journal) {
            $originalStatus = $journal->attributes['original_status'] ?? $journal->getOriginal('status');
            $currentStatus = $journal->status;
            
            // Normalize status values for comparison
            $originalValue = $originalStatus instanceof JournalStatus ? $originalStatus : JournalStatus::tryFrom($originalStatus ?? '');
            $currentValue = $currentStatus instanceof JournalStatus ? $currentStatus : JournalStatus::tryFrom($currentStatus ?? '');
            
            // Check if it was posted when retrieved, in database, or currently
            if ($originalValue === JournalStatus::POSTED || $currentValue === JournalStatus::POSTED) {
                throw new \RuntimeException(
                    'Cannot delete a posted journal. Posted journals are immutable.'
                );
            }
        });
    }

    /**
     * Get the original status when the model was retrieved
     */
    public function getOriginalStatusAttribute()
    {
        return $this->attributes['original_status'] ?? $this->getOriginal('status');
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

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function isDraft(): bool
    {
        return $this->status === JournalStatus::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === JournalStatus::POSTED;
    }

    public function isVoid(): bool
    {
        return $this->status === JournalStatus::VOID;
    }

    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }
}

