<?php

namespace App\Filament\Admin\Resources\CourseSectionResource\Pages;

use App\Filament\Admin\Resources\CourseSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourseSection extends ViewRecord
{
    protected static string $resource = CourseSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
