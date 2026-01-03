<?php

namespace App\Filament\Admin\Resources\App\Domain\Branch\Models\BranchResource\Pages;

use App\Filament\Admin\Resources\App\Domain\Branch\Models\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;
}
