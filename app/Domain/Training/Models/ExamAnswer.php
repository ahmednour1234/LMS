<?php

namespace App\Domain\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer',
        'answer_text',
        'selected_option',
        'points_earned',
        'points_possible',
        'feedback',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'string',
            'answer_text' => 'string',
            'selected_option' => 'string',
            'points_earned' => 'decimal:2',
            'points_possible' => 'decimal:2',
            'feedback' => 'string',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }
}
