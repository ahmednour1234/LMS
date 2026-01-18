<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'summary' => $this->resource['summary'] ?? [],
            'details' => $this->resource['details'] ?? [],
        ];
    }
}
