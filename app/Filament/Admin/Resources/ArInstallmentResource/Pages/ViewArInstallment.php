<?php

namespace App\Filament\Admin\Resources\ArInstallmentResource\Pages;

use App\Filament\Admin\Resources\ArInstallmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArInstallment extends ViewRecord
{
    protected static string $resource = ArInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
