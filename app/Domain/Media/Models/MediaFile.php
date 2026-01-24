<?php

namespace App\Domain\Media\Models;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Models\LessonItem;
use App\Domain\Training\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
        'user_id',
        'teacher_id',
        'branch_id',
        'is_private',
        'access_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_private' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lessonItems(): HasMany
    {
        return $this->hasMany(LessonItem::class);
    }

    public function taskSubmissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }
}

