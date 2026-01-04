<?php

namespace App\Domain\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'sex',
        'photo',
        'active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function ownedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'owner_teacher_id');
    }

    public function assignedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_teacher', 'teacher_id', 'course_id');
    }
}

