<?php

namespace App\Domain\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'type',
        'question',
        'options',
        'correct_answer',
        'points',
        'order',
        'is_active',
        'required',
    ];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'options' => 'array',
            'correct_answer' => 'integer',
            'points' => 'integer',
            'order' => 'integer',
            'is_active' => 'boolean',
            'required' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public static function getTrueFalseOptions(): array
    {
        return [
            [
                'text' => 'True',
                'is_correct' => true,
                'order' => 1,
            ],
            [
                'text' => 'False',
                'is_correct' => false,
                'order' => 2,
            ],
        ];
    }
}

