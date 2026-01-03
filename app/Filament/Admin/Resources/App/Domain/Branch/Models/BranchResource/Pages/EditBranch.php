<?php

namespace App\Filament\Admin\Resources\App\Domain\Branch\Models\BranchResource\Pages;

use App\Filament\Admin\Resources\App\Domain\Branch\Models\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
