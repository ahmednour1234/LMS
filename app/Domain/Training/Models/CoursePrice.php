<?php

namespace App\Domain\Training\Models;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Enums\DeliveryType;
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
            'price' => 'decimal:2',
            'session_price' => 'decimal:2',
            'sessions_count' => 'integer',
            'allow_installments' => 'boolean',
            'min_down_payment' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

