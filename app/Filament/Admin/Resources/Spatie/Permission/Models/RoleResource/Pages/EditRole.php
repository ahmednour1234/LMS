<?php

namespace App\Filament\Admin\Resources\Spatie\Permission\Models\RoleResource\Pages;

use App\Filament\Admin\Resources\Spatie\Permission\Models\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
