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

        if (empty($data['cash_bank_account_id']) || empty($data['counterparty_account_id'])) {
            Notification::make()
                ->danger()
                ->title(__('validation.required'))
                ->body(__('vouchers.errors.accounts_required'))
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
        }

        if (empty($data['amount']) || (float) $data['amount'] <= 0) {
            Notification::make()
                ->danger()
                ->title(__('validation.required'))
                ->body(__('vouchers.errors.amount_required'))
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
        }

        return $data;
    }
}
