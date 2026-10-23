<?php

namespace App\Support\Tilkit;

readonly class GateResult
{
    public function __construct(
        public bool $eligible,
        public ?string $exclusionReason,
        public string $tallyStatusLabel,
    ) {}

    public static function eligible(string $label = 'Counted in tally'): self
    {
        return new self(true, null, $label);
    }

    public static function excluded(string $reason, string $label): self
    {
        return new self(false, $reason, $label);
    }
}
