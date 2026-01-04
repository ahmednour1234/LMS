<?php

namespace App\Filament\Admin\Resources\Domain\Training\Models\TeacherResource\Pages;

use App\Filament\Admin\Resources\Domain\Training\Models\TeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;
}
