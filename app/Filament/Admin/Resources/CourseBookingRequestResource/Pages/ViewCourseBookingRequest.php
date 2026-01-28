<?php

namespace App\Filament\Admin\Resources\CourseBookingRequestResource\Pages;

use App\Filament\Admin\Resources\CourseBookingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourseBookingRequest extends ViewRecord
{
    protected static string $resource = CourseBookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
