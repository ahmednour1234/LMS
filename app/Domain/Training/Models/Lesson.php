<?php

namespace App\Domain\Training\Models;

use App\Domain\Training\Enums\LessonType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'title',
        'description',
        'lesson_type',
        'sort_order',
        'is_preview',
        'is_active',
        'estimated_minutes',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'lesson_type' => LessonType::class,
            'sort_order' => 'integer',
            'is_preview' => 'boolean',
            'is_active' => 'boolean',
            'estimated_minutes' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LessonItem::class)->orderBy('order');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

