<?php

namespace App\Filament\Admin\Resources\ArInvoiceResource\Pages;

use App\Filament\Admin\Resources\ArInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArInvoices extends ListRecords
{
    protected static string $resource = ArInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
