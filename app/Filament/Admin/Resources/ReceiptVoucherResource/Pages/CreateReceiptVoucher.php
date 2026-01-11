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
