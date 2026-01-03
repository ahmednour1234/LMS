<?php

namespace App\Filament\Admin\Resources\App\Domain\Branch\Models\BranchResource\Pages;

use App\Filament\Admin\Resources\App\Domain\Branch\Models\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBranches extends ListRecords
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
