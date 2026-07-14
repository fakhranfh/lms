<?php

namespace App\Enums;

enum TierChangeType: string
{
    case Initial = 'initial';
    case Upgrade = 'upgrade';
    case Downgrade = 'downgrade';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Initial Assignment',
            self::Upgrade => 'Upgrade',
            self::Downgrade => 'Downgrade',
        };
    }
}
