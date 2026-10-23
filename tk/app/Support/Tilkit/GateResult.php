<?php

namespace App\Support\Tilkit;

readonly class GateResult
{
    public function __construct(
        public bool $eligible,
        public ?string $exclusionReason,
    ) {}

    public static function eligible(): self
    {
        return new self(true, null);
    }

    public static function excluded(string $reason): self
    {
        return new self(false, $reason);
    }
}
