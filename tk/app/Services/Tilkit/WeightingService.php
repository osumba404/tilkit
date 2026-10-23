<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Support\Tilkit\IndicatorHistory;

readonly class WeightingResult
{
    public function __construct(
        public string $phase,
        public float $baseWeight,
        public float $finalWeight,
        public float $tallyWeight,
        public float $priorMultiplier,
        public float $priorityAdjustedWeight,
        public bool $stabilityFlag,
        public bool $corroborationCapApplied,
    ) {}
}

class WeightingService
{
    public function compute(
        TilkitIndicatorRecord $record,
        IndicatorHistory $history,
        ?TilkitSeasonSettings $settings,
        float $hazardPriorityFactor = 1.0,
    ): WeightingResult {
        $scored = $history->scoredSeasons;
        $threshold = config('tilkit.weather.engine.scored_seasons_for_hit_rate', 3);
        $phase = $scored >= $threshold ? 'hit-rate-based' : 'rating-based';

        $reliability = (int) ($record->current_reliability?->value ?? 0);
        $historical = $this->historicalReliability($record);

        if ($phase === 'rating-based') {
            $base = (float) $reliability;
        } else {
            $rate = $history->hitRate() ?? 0.0;
            $base = $rate >= 0.70 ? 3.0 : ($rate >= 0.40 ? 2.0 : 1.0);
        }

        $capApplied = false;
        if ($history->corroboration < 2) {
            if ($base > 2.0) {
                $capApplied = true;
            }
            $base = min($base, 2.0);
        }

        $relevance = (int) ($record->decision_relevance?->value ?? 0);
        if ($relevance <= 0) {
            $relevance = 1;
        }

        $final = $base * $relevance;
        $prior = $this->priorMultiplier($record);
        $tallyWeight = $final;

        $usePrior = $settings?->use_prior_multiplier ?? false;
        if ($usePrior && $phase === 'rating-based' && $reliability > 0) {
            $tallyWeight = $final * $prior;
        }

        $priorityAdjusted = $tallyWeight * $hazardPriorityFactor;

        $stability = $historical !== null
            && $reliability > 0
            && $historical > 0
            && abs($reliability - $historical) >= 2;

        return new WeightingResult(
            phase: $phase,
            baseWeight: $base,
            finalWeight: $final,
            tallyWeight: $tallyWeight,
            priorMultiplier: $prior,
            priorityAdjustedWeight: $priorityAdjusted,
            stabilityFlag: $stability,
            corroborationCapApplied: $capApplied,
        );
    }

    private function historicalReliability(TilkitIndicatorRecord $record): ?int
    {
        $map = app(WorkbookRecordService::class)->rawWorkbookMap($record);

        return isset($map['L']) && is_numeric($map['L']) ? (int) $map['L'] : null;
    }

    private function priorMultiplier(TilkitIndicatorRecord $record): float
    {
        $record->loadMissing('indicator.code');
        $code = $record->indicator?->code;
        if ($code?->prior_multiplier !== null) {
            return (float) $code->prior_multiplier;
        }

        return 1.0;
    }
}
