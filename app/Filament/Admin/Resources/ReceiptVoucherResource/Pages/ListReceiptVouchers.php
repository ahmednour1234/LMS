<?php

namespace App\Filament\Admin\Resources\ReceiptVoucherResource\Pages;

use App\Filament\Admin\Resources\ReceiptVoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceiptVouchers extends ListRecords
{
    protected static string $resource = ReceiptVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
