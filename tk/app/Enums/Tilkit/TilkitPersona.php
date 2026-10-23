<?php

namespace App\Enums\Tilkit;

/** Developer Brief §7 — field roles for the weather module. */
enum TilkitPersona: string
{
    case Observer = 'observer';
    case WorkshopContributor = 'workshop_contributor';
    case LeadItkHolder = 'lead_itk_holder';
    case Facilitator = 'facilitator';
    case DataDesk = 'data_desk';
    case TechnicalVerifier = 'technical_verifier';
    case CommunityRepresentative = 'community_representative';
    case IntegrationTeam = 'integration_team';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Observer => 'Observer',
            self::WorkshopContributor => 'Workshop contributor',
            self::LeadItkHolder => 'Lead ITK holder',
            self::Facilitator => 'Facilitator',
            self::DataDesk => 'Data desk',
            self::TechnicalVerifier => 'Technical verifier',
            self::CommunityRepresentative => 'Community representative',
            self::IntegrationTeam => 'Integration team',
            self::Administrator => 'Administrator',
        };
    }

    public function canLogSighting(): bool
    {
        return in_array($this, [self::Observer, self::WorkshopContributor, self::Facilitator, self::Administrator], true);
    }

    public function canConfirm(): bool
    {
        return in_array($this, [self::WorkshopContributor, self::LeadItkHolder, self::Facilitator, self::Administrator], true);
    }

    public function canApprove(): bool
    {
        return in_array($this, [self::LeadItkHolder, self::Administrator], true);
    }

    public function canVerify(): bool
    {
        return in_array($this, [self::TechnicalVerifier, self::Administrator], true);
    }

    public function canDataDesk(): bool
    {
        return in_array($this, [self::DataDesk, self::IntegrationTeam, self::Administrator], true);
    }

    public function canManageIntegration(): bool
    {
        return in_array($this, [self::IntegrationTeam, self::Administrator], true);
    }

    public function canSignOffWeightReview(): bool
    {
        return in_array($this, [self::LeadItkHolder, self::TechnicalVerifier, self::Administrator], true);
    }
}
