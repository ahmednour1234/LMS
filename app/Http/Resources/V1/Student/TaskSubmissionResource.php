<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TaskSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'submission_text' => $this->submission_text,
            'score' => $this->score ? (float) $this->score : null,
            'feedback' => $this->feedback,
            'status' => $this->status,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'media_file' => $this->whenLoaded('mediaFile', function () {
                return [
                    'id' => $this->mediaFile->id,
                    'url' => Storage::url($this->mediaFile->path ?? ''),
                    'name' => $this->mediaFile->name,
                ];
            }),
        ];
    }
}
