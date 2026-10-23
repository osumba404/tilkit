<?php

namespace App\Support\Tilkit;

readonly class IndicatorHistory
{
    public function __construct(
        public int $corroboration,
        public int $scoredSeasons,
        public int $hits,
        public int $misses,
        public int $falseStarts,
    ) {}

    public function hitRate(): ?float
    {
        if ($this->scoredSeasons === 0) {
            return null;
        }

        return $this->hits / $this->scoredSeasons;
    }
}
