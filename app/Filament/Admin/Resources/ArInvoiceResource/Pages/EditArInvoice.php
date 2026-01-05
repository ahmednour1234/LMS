<?php

namespace App\Filament\Admin\Resources\ArInvoiceResource\Pages;

use App\Filament\Admin\Resources\ArInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArInvoice extends EditRecord
{
    protected static string $resource = ArInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
