<?php

namespace App\Filament\Admin\Resources\ReceiptVoucherResource\Actions;

use App\Domain\Accounting\Models\Voucher;
use Filament\Tables\Actions\Action;

class PrintAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'print';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('vouchers.actions.print'));
        $this->icon('heroicon-o-printer');
        $this->color('gray');
        $this->url(fn (Voucher $record) => route('filament.admin.resources.receipt-vouchers.print', $record))
            ->openUrlInNewTab();

        $this->visible(fn (Voucher $record) => auth()->user()->can('print', $record));
    }
}
