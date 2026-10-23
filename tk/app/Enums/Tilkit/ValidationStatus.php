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

    /** Wording from the Indicator Log AP dropdown (Community Data List). */
    public function excelLabel(): string
    {
        return match ($this) {
            self::FgdValidated => 'FGD-validated only',
            self::CommunityPlenaryEndorsed => 'Community/Plenary-endorsed',
            self::SubCommunitySpecific => 'Sub-community-specific',
            self::ProvisionalEmerging => 'Provisional/emerging',
            self::Contested => 'Contested',
        };
    }
}
