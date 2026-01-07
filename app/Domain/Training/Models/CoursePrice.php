<?php

namespace App\Domain\Training\Models;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Enums\DeliveryType;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePrice extends Model
{
    use HasFactory;

    protected $table = 'course_prices';

    protected $fillable = [
        'course_id',
        'branch_id',
        'delivery_type',
        'pricing_mode',
        'price',
        'session_price',
        'sessions_count',
        'allow_installments',
        'min_down_payment',
        'max_installments',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'delivery_type' => DeliveryType::class,
            'pricing_mode' => 'string',
            'price' => 'decimal:3',
            'session_price' => 'decimal:3',
            'sessions_count' => 'integer',
            'allow_installments' => 'boolean',
            'min_down_payment' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (CoursePrice $coursePrice) {
            $coursePrice->validateBusinessRules();
        });
    }

    /**
     * Validate business rules for course pricing.
     * 
     * @throws BusinessException
     */
    protected function validateBusinessRules(): void
    {
        $pricingMode = $this->pricing_mode ?? 'course_total';

        // Validate price is set for course_total and both modes
        if (in_array($pricingMode, ['course_total', 'both'])) {
            if (empty($this->price) || (float) $this->price <= 0) {
                throw new BusinessException('Price is required for course total pricing mode.');
            }
        }

        // Validate session_price and sessions_count for per_session and both modes
        if (in_array($pricingMode, ['per_session', 'both'])) {
            if (empty($this->session_price) || (float) $this->session_price <= 0) {
                throw new BusinessException('Session price is required for per-session pricing mode.');
            }
            if (empty($this->sessions_count) || (int) $this->sessions_count < 1) {
                throw new BusinessException('Sessions count must be at least 1 for per-session pricing mode.');
            }
        }

        // Per-session mode should not allow installments
        if ($pricingMode === 'per_session' && $this->allow_installments) {
            $this->allow_installments = false;
        }

        // Validate installment settings
        if ($this->allow_installments) {
            // max_installments must be at least 1
            if (empty($this->max_installments) || (int) $this->max_installments < 1) {
                throw new BusinessException('Maximum installments must be at least 1 when installments are allowed.');
            }

            // min_down_payment cannot exceed price
            if ($this->min_down_payment !== null && (float) $this->min_down_payment > 0) {
                $effectivePrice = (float) $this->price;
                if ($effectivePrice > 0 && (float) $this->min_down_payment > $effectivePrice) {
                    throw new BusinessException('Minimum down payment cannot exceed the course price.');
                }
            }

            // max_installments should be reasonable (max 24 months)
            if ((int) $this->max_installments > 24) {
                throw new BusinessException('Maximum installments cannot exceed 24.');
            }
        }
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the effective price based on pricing mode and choice.
     * 
     * @param string $choice 'full' or 'per_session'
     * @param int|null $sessionQty Number of sessions (for per_session)
     * @return float
     */
    public function getEffectivePrice(string $choice = 'full', ?int $sessionQty = null): float
    {
        $pricingMode = $this->pricing_mode ?? 'course_total';

        if ($choice === 'per_session' && in_array($pricingMode, ['per_session', 'both'])) {
            $qty = $sessionQty ?? $this->sessions_count ?? 1;
            return (float) $this->session_price * $qty;
        }

        return (float) $this->price;
    }

    /**
     * Check if this price allows the specified pricing choice.
     * 
     * @param string $choice 'full', 'per_session', or 'installment'
     * @return bool
     */
    public function allowsChoice(string $choice): bool
    {
        $pricingMode = $this->pricing_mode ?? 'course_total';

        return match ($choice) {
            'full' => in_array($pricingMode, ['course_total', 'both']),
            'per_session' => in_array($pricingMode, ['per_session', 'both']),
            'installment' => $pricingMode !== 'per_session' && $this->allow_installments,
            default => false,
        };
    }
}

