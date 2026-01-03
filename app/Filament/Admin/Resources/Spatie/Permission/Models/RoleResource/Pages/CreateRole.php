<?php

namespace App\Filament\Admin\Resources\Spatie\Permission\Models\RoleResource\Pages;

use App\Filament\Admin\Resources\Spatie\Permission\Models\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
