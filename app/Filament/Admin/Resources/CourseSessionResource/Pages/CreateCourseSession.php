<?php

namespace App\Filament\Admin\Resources\CourseSessionResource\Pages;

use App\Filament\Admin\Resources\CourseSessionResource;
use App\Http\Services\CourseSessionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\App;

class CreateCourseSession extends CreateRecord
{
    protected static string $resource = CourseSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = App::make(CourseSessionService::class);
        
        if (isset($data['provider']) && $data['provider'] === 'jitsi') {
            $data['room_slug'] = $service->generateRoomSlug();
        }

        if (isset($data['location_type']) && $data['location_type'] === 'onsite') {
            $data['onsite_qr_secret'] = $service->generateQrSecret();
        }

        return $data;
    }
}
