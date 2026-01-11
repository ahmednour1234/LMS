<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherSequence extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type',
        'last_number',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
