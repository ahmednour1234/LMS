<?php

namespace App\Filament\Admin\Resources\ArInstallmentResource\Pages;

use App\Filament\Admin\Resources\ArInstallmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArInstallments extends ListRecords
{
    protected static string $resource = ArInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
