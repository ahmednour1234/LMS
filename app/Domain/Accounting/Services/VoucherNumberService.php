<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\VoucherSequence;
use App\Enums\VoucherType;
use Illuminate\Support\Facades\DB;

class VoucherNumberService
{
    public function generateNextNumber(VoucherType $type): string
    {
        return DB::transaction(function () use ($type) {
            $sequence = VoucherSequence::lockForUpdate()
                ->where('type', $type->value)
                ->first();

            if (!$sequence) {
                $sequence = VoucherSequence::create([
                    'type' => $type->value,
                    'last_number' => 0,
                ]);
            }

            $sequence->last_number += 1;
            $sequence->updated_at = now();
            $sequence->save();

            $prefix = $type === VoucherType::RECEIPT ? 'RV' : 'PV';
            return sprintf('%s-%06d', $prefix, $sequence->last_number);
        });
    }
}
