<?php

namespace App\Domain\Training\Enums;

enum SessionStatus: string
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => __('training.enums.session_status.scheduled'),
            self::COMPLETED => __('training.enums.session_status.completed'),
            self::CANCELLED => __('training.enums.session_status.cancelled'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
