<?php

namespace App\Filament\Admin\Resources\CourseSectionResource\Pages;

use App\Filament\Admin\Resources\CourseSectionResource;
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
