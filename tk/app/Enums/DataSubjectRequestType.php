<?php

namespace App\Enums;

enum DataSubjectRequestType: string
{
    case Access = 'access';
    case Correction = 'correction';
    case Erasure = 'erasure';
    case Objection = 'objection';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Access (s.26(b))',
            self::Correction => 'Correction (s.26(d))',
            self::Erasure => 'Erasure (s.26(e))',
            self::Objection => 'Object to processing (s.26(c))',
        };
    }
}
