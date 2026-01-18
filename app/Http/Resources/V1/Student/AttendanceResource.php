<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'status' => $this->status->value,
            'method' => $this->method->value,
            'marked_at' => $this->marked_at?->toISOString(),
        ];
    }
}
