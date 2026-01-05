<?php

namespace App\Filament\Admin\Resources\ArInstallmentResource\Pages;

use App\Filament\Admin\Resources\ArInstallmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArInstallment extends EditRecord
{
    protected static string $resource = ArInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
