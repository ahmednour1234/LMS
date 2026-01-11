<?php

namespace App\Filament\Admin\Resources\ReceiptVoucherResource\Pages;

use App\Domain\Accounting\Models\Voucher;
use App\Enums\VoucherType;
use App\Filament\Admin\Resources\ReceiptVoucherResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReceiptVoucher extends CreateRecord
{
    protected static string $resource = ReceiptVoucherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        $data['voucher_type'] = VoucherType::RECEIPT->value;
        
        if (!$user->isSuperAdmin() && !isset($data['branch_id'])) {
            $data['branch_id'] = $user->branch_id;
        }
        $data['created_by'] = $user->id;

        if (isset($data['voucherLines'])) {
            $debitSum = collect($data['voucherLines'])->sum('debit');
            $creditSum = collect($data['voucherLines'])->sum('credit');

            if (abs($debitSum - $creditSum) > 0.01) {
                Notification::make()
                    ->danger()
                    ->title(__('vouchers.errors.imbalanced'))
                    ->body(__('vouchers.errors.debit_credit_mismatch', [
                        'debit' => number_format($debitSum, 2),
                        'credit' => number_format($creditSum, 2),
                    ]))
                    ->send();

                throw new \Filament\Support\Exceptions\Halt();
            }
        }

        return $data;
    }
}
