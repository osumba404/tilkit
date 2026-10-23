<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitSeasonSettings;
use App\Support\Tilkit\IndicatorHistory;

/**
 * v4 derived columns DJ–DT extensions (Brief §6).
 */
class V4FieldDeriver
{
    /** @param  array<string, mixed>  $map */
    public function hazardPriorityFactor(array $map, ?TilkitSeasonSettings $settings): float
    {
        $hazards = $settings?->settings['hazard_priority'] ?? TilkitSeasonSettings::defaultSettings()['hazard_priority'];
        $bu = isset($map['BU']) ? (int) $map['BU'] : 0;

        return match ($bu) {
            1 => (float) ($hazards['drought'] ?? 1.5),
            2 => (float) ($hazards['flood'] ?? 1.5),
            4 => (float) ($hazards['erratic'] ?? 1.25),
            6 => (float) ($hazards['pest'] ?? 1.25),
            default => (float) ($hazards['default'] ?? 1.0),
        };
    }

    /** @param  array<string, mixed>  $map */
    public function kmsaAgreement(?int $communityTercile, array $map, ?TilkitSeasonSettings $settings): ?string
    {
        $kmsa = isset($map['DJ']) && is_numeric($map['DJ'])
            ? (int) $map['DJ']
            : ($settings?->kmsa_outlook_tercile);
        if ($communityTercile === null || $kmsa === null || $kmsa === 0) {
            return null;
        }
        if ($communityTercile === $kmsa) {
            return 'Agrees';
        }
        if (abs($communityTercile - $kmsa) === 1) {
            return 'Differs by one class';
        }

        return 'Opposite';
    }

    public function vegetationClassFromVci(?float $vci): ?int
    {
        if ($vci === null) {
            return null;
        }

        return match (true) {
            $vci >= 35 => 3,
            $vci >= 20 => 2,
            default => 1,
        };
    }

    /** @param  array<string, mixed>  $map */
    public function vegetationCheck(?int $predictedCq, ?int $actualCs): ?string
    {
        if ($predictedCq === null || $actualCs === null || $predictedCq === 0) {
            return null;
        }
        if ($predictedCq === $actualCs) {
            return 'Matched';
        }
        if (abs($predictedCq - $actualCs) === 1) {
            return 'Off by one class';
        }

        return 'Missed';
    }

    /** @param  array<string, mixed>  $map */
    public function climateChangeFlag(array $map, IndicatorHistory $hist, bool $stabilityFlag, ?float $hitRate): ?string
    {
        $signals = 0;
        $cm = isset($map['CM']) && is_numeric($map['CM']) ? abs((int) $map['CM']) : 0;
        if ($cm >= 1) {
            $signals++;
        }
        if ($stabilityFlag) {
            $signals++;
        }
        if (! empty($map['CN']) && ! in_array(strtolower((string) $map['CN']), ['no change', 'none', ''], true)) {
            $signals++;
        }
        $l = isset($map['L']) && is_numeric($map['L']) ? (int) $map['L'] : null;
        $m = isset($map['M']) && is_numeric($map['M']) ? (int) $map['M'] : null;
        if ($l !== null && $m !== null && $l > 0 && $m > 0 && ($l - $m) >= 1) {
            $signals++;
        }
        if ($hitRate !== null && $hist->scoredSeasons >= 3 && $hitRate < 0.40) {
            $signals++;
        }

        return match (true) {
            $signals >= 2 => 'STRONG',
            $signals === 1 => 'Watch',
            default => null,
        };
    }

    /** @param  array<string, mixed>  $map */
    public function inclusionShare(array $map): ?float
    {
        $dd = isset($map['DD']) && is_numeric($map['DD']) ? (float) $map['DD'] : null;
        $de = isset($map['DE']) && is_numeric($map['DE']) ? (float) $map['DE'] : null;
        if ($dd === null || $dd <= 0 || $de === null) {
            return null;
        }

        return round($de / $dd, 4);
    }

    /** @param  array<string, mixed>  $map */
    public function warningLeadDays(array $map): ?int
    {
        $onset = $map['P'] ?? null;
        $disseminated = $map['DB'] ?? null;
        if (! $onset || ! $disseminated) {
            return null;
        }
        try {
            return (int) \Carbon\Carbon::parse($disseminated)->diffInDays(\Carbon\Carbon::parse($onset), false);
        } catch (\Throwable) {
            return null;
        }
    }
}
