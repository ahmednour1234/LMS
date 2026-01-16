<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // resource = array coming from service
        return [
            'cards' => $this->resource['cards'],
            'charts' => $this->resource['charts'],
        ];
    }
}
