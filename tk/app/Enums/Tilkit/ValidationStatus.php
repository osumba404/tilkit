<?php

namespace App\Enums\Tilkit;

enum ValidationStatus: string
{
    case FgdValidated = 'fgd_validated';
    case CommunityPlenaryEndorsed = 'community_plenary_endorsed';
    case SubCommunitySpecific = 'sub_community_specific';
    case ProvisionalEmerging = 'provisional_emerging';
    case Contested = 'contested';

    public function excludedFromTally(): bool
    {
        return $this === self::Contested;
    }
}
