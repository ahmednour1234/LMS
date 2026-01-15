<?php

namespace App\Filament\Admin\Resources\CourseSessionResource\Pages;

use App\Filament\Admin\Resources\CourseSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseSessions extends ListRecords
{
    protected static string $resource = CourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
