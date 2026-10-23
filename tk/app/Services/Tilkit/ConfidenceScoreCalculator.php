<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\ValidationStatus;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Support\Tilkit\IndicatorHistory;

class ConfidenceScoreCalculator
{
    /** @param  array<string, mixed>  $workbookMap */
    public function score(
        TilkitIndicatorRecord $record,
        IndicatorHistory $history,
        array $workbookMap,
        float $baseWeight,
        bool $stabilityFlag,
        ?TilkitSeasonSettings $settings = null,
    ): int {
        $weights = $settings?->settings['confidence_weights'] ?? [
            'track_record' => 25,
            'corroboration' => 15,
            'validation_breadth' => 15,
            'lead_time' => 10,
            'stability' => 10,
            'kmsa_agreement' => 10,
            'observers' => 5,
            'verified' => 10,
        ];

        $dims = [
            'track_record' => min(1.0, $baseWeight / 3.0),
            'corroboration' => min(1.0, $history->corroboration / 3.0),
            'validation_breadth' => $this->validationBreadth($record->validation_status),
            'lead_time' => $this->leadTimeScore($workbookMap['K'] ?? null),
            'stability' => $stabilityFlag ? 0.25 : 1.0,
            'kmsa_agreement' => 0.5,
            'observers' => min(1.0, ((float) ($workbookMap['BG'] ?? 0)) / 3.0),
            'verified' => ($record->verified ?? false) ? 1.0 : 0.0,
        ];

        $totalWeight = array_sum($weights) ?: 100;
        $score = 0.0;
        foreach ($weights as $key => $w) {
            $score += ($w / $totalWeight) * ($dims[$key] ?? 0) * 100;
        }

        return (int) round(max(0, min(100, $score)));
    }

    private function validationBreadth(?ValidationStatus $status): float
    {
        return match ($status) {
            ValidationStatus::CommunityPlenaryEndorsed => 1.0,
            ValidationStatus::FgdValidated => 0.75,
            ValidationStatus::SubCommunitySpecific => 0.5,
            ValidationStatus::ProvisionalEmerging => 0.25,
            ValidationStatus::Contested => 0.0,
            default => 0.5,
        };
    }

    private function leadTimeScore(mixed $k): float
    {
        if (! is_numeric($k)) {
            return 0.5;
        }

        return min(1.0, ((int) $k) / 3.0);
    }
}
