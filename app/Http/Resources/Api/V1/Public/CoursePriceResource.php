<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoursePriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'branch_id' => $this->branch_id,
            'delivery_type' => $this->delivery_type?->value,
            'pricing_mode' => $this->pricing_mode ?? 'course_total',
            'price' => $this->price ? (float) $this->price : null,
            'session_price' => $this->session_price ? (float) $this->session_price : null,
            'sessions_count' => $this->sessions_count,
            'allow_installments' => $this->allow_installments,
            'min_down_payment' => $this->min_down_payment ? (float) $this->min_down_payment : null,
            'max_installments' => $this->max_installments,
            'active' => $this->is_active, // Transform is_active to active
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

