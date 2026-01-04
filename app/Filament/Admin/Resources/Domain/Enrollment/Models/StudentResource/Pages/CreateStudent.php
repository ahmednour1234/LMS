<?php

namespace App\Filament\Admin\Resources\Domain\Enrollment\Models\StudentResource\Pages;

use App\Filament\Admin\Resources\Domain\Enrollment\Models\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
}
