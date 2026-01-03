<?php

namespace App\Filament\Admin\Resources\Spatie\Permission\Models\PermissionResource\Pages;

use App\Filament\Admin\Resources\Spatie\Permission\Models\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
