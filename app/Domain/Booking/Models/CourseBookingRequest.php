<?php

namespace App\Domain\Booking\Models;

use App\Domain\Training\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseBookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'educational_stage',
        'phone',
        'gender',
        'message',
        'status',
        'admin_notes',
        'course_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('educational_stage', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%");
        });
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace('/[\s\-]/', '', $value);
    }
}
