<?php

namespace App\Filament\Teacher\Resources\Training\SessionResource\Pages;

use App\Filament\Teacher\Resources\Training\SessionResource;
use App\Http\Services\CourseSessionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\App;

class CreateSession extends CreateRecord
{
    protected static string $resource = SessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['teacher_id'] = auth('teacher')->id();
        
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
