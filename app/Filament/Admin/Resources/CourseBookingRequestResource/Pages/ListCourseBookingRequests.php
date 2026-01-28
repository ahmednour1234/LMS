<?php

namespace App\Filament\Admin\Resources\CourseBookingRequestResource\Pages;

use App\Filament\Admin\Resources\CourseBookingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseBookingRequests extends ListRecords
{
    protected static string $resource = CourseBookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
