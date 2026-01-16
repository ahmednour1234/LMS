<?php

namespace App\Domain\Training\Enums;

enum SessionLocationType: string
{
    case ONLINE = 'online';
    case ONSITE = 'onsite';
    case HYBRID = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => __('training.enums.session_location_type.online'),
            self::ONSITE => __('training.enums.session_location_type.onsite'),
            self::HYBRID => __('training.enums.session_location_type.hybrid'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
