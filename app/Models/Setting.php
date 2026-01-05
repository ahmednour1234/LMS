<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_system' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include system settings.
     */
    public function scopeSystemSettings(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to only include non-system settings.
     */
    public function scopeNonSystemSettings(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * Check if this setting is a system setting.
     */
    public function isSystemSetting(): bool
    {
        return $this->is_system === true;
    }
}

