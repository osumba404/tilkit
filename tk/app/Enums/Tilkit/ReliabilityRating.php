<?php

namespace App\Enums\Tilkit;

enum ReliabilityRating: int
{
    case NotSure = 0;
    case Low = 1;
    case Medium = 2;
    case High = 3;

    public function countsInAverage(): bool
    {
        return $this !== self::NotSure;
    }
}
