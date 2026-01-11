<?php

namespace App\Filament\Admin\Resources\PaymentVoucherResource\Pages;

use App\Enums\VoucherType;
use App\Filament\Admin\Resources\PaymentVoucherResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentVoucher extends CreateRecord
{
    protected static string $resource = PaymentVoucherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        $data['voucher_type'] = VoucherType::PAYMENT->value;
        
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
