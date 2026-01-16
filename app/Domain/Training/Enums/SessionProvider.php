<?php

namespace App\Domain\Training\Enums;

enum SessionProvider: string
{
    case JITSI = 'jitsi';
    // add later: zoom, teams, meet...

    public function label(): string
    {
        return match ($this) {
            self::JITSI => __('training.enums.session_provider.jitsi'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
