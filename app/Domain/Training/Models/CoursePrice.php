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
        'price',
        'allow_installments',
        'min_down_payment',
        'max_installments',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'delivery_type' => DeliveryType::class,
            'price' => 'decimal:2',
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

