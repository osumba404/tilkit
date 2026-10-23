<?php

namespace App\Enums\Tilkit;

enum TercileForecast: int
{
    case NoPrediction = 0;
    case BelowNormal = 1;
    case Normal = 2;
    case AboveNormal = 3;

    public function label(): string
    {
        return match ($this) {
            self::AboveNormal => 'above normal',
            self::Normal => 'normal',
            self::BelowNormal => 'below normal',
            self::NoPrediction => 'no prediction',
        };
    }

    public function isForecast(): bool
    {
        return $this !== self::NoPrediction;
    }
}
