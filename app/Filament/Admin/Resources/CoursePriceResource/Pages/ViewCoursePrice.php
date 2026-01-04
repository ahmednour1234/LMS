<?php

namespace App\Filament\Admin\Resources\CoursePriceResource\Pages;

use App\Filament\Admin\Resources\CoursePriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCoursePrice extends ViewRecord
{
    protected static string $resource = CoursePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
