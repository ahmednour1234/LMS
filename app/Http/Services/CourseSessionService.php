<?php

namespace App\Http\Services;

use App\Domain\Training\Models\CourseSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CourseSessionService
{
    public function create(array $data): CourseSession
    {
        if (isset($data['provider']) && $data['provider'] === 'jitsi') {
            $data['room_slug'] = $this->generateRoomSlug();
        }

        if (isset($data['location_type']) && $data['location_type'] === 'onsite') {
            $data['onsite_qr_secret'] = $this->generateQrSecret();
        }

        return CourseSession::create($data);
    }

    public function update(int $id, array $data): CourseSession
    {
        $session = CourseSession::findOrFail($id);

        if (isset($data['provider']) && $data['provider'] === 'jitsi' && !$session->room_slug) {
            $data['room_slug'] = $this->generateRoomSlug();
        }

        if (isset($data['location_type']) && $data['location_type'] === 'onsite' && !$session->onsite_qr_secret) {
            $data['onsite_qr_secret'] = $this->generateQrSecret();
        }

        $session->update($data);

        return $session->refresh();
    }

    public function findByCourse(int $courseId): Collection
    {
        return CourseSession::where('course_id', $courseId)
            ->orderBy('starts_at', 'asc')
            ->get();
    }

    public function generateRoomSlug(): string
    {
        do {
            $slug = Str::random(32);
        } while (CourseSession::where('room_slug', $slug)->exists());

        return $slug;
    }

    public function generateQrSecret(): string
    {
        return Str::random(64);
    }
}
