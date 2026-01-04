<?php

namespace App\Filament\Admin\Resources\Domain\Training\Models\TeacherResource\Pages;

use App\Filament\Admin\Resources\Domain\Training\Models\TeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeachers extends ListRecords
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
