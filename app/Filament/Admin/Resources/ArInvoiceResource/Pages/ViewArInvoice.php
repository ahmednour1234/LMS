<?php

namespace App\Filament\Admin\Resources\ArInvoiceResource\Pages;

use App\Filament\Admin\Resources\ArInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArInvoice extends ViewRecord
{
    protected static string $resource = ArInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
