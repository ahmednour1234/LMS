<?php

namespace App\Filament\Teacher\Resources\Training\CourseSectionResource\Pages;

use App\Filament\Teacher\Resources\Training\CourseSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseSections extends ListRecords
{
    protected static string $resource = CourseSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
