<?php

namespace App\Http\Resources\Api\V1\Teacher;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    /**body
     * @bodyParam id integer required Example: 1
     * @bodyParam task_id integer required Example: 1
     * @bodyParam student_id integer required Example: 1
     * @bodyParam submission_text string optional Example: This is a submission text.
     * @bodyParam media_file_id integer optional Example: 1
     * @bodyParam score numeric optional Example: 10
     * @bodyParam feedback object optional Example: {"ar":"Good work!","en":"Good work!"}
     * @bodyParam status string optional Example: pending
     * @bodyParam reviewed_at datetime optional Example: 2026-01-13 12:00:00
     * @bodyParam reviewed_by integer optional Example: 1
     * @bodyParam created_at datetime optional Example: 2026-01-13 12:00:00
     * @bodyParam student object optional Example: {"id":1,"name":"John Doe"}
     * @bodyParam media object optional Example: {"id":1,"path":"/path/to/media.pdf","mime_type":"application/pdf","size":1000}
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'student_id' => $this->student_id,

            'submission_text' => $this->submission_text,
            'media_file_id' => $this->media_file_id,

            'score' => $this->score,
            'feedback' => $this->feedback,

            'status' => $this->status,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'reviewed_by' => $this->reviewed_by,

            'created_at' => $this->created_at?->toISOString(),

            'student' => $this->whenLoaded('student', fn() => [
                'id' => $this->student->id,
                'name' => $this->student->name ?? null,
            ]),
            'media' => $this->whenLoaded('mediaFile', fn() => [
                'id' => $this->mediaFile->id,
                'path' => $this->mediaFile->path,
                'mime_type' => $this->mediaFile->mime_type,
                'size' => $this->mediaFile->size,
            ]),
        ];
    }
}
