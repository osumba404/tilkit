<?php

namespace App\Support\Tilkit;

readonly class TallyResult
{
    /** @param  array<int, array{count: int, percent: float}>  $simple  Keys 1|2|3 */
    /** @param  array<int, array{weight: float, percent: float}>  $weighted */
    /** @param  array<int, array{weight: float, percent: float}>  $priorityAdjusted */
    /** @param  array<string, int>  $exclusionCounts */
    public function __construct(
        public int $siteId,
        public int $seasonId,
        public int $countedRows,
        public array $simple,
        public array $weighted,
        public array $priorityAdjusted,
        public ?int $leadingSimple,
        public ?int $leadingWeighted,
        public ?int $leadingPriority,
        public bool $simpleWeightedAgree,
        public string $reconciliation,
        public array $exclusionCounts,
        public bool $isTieSimple,
        public bool $isTieWeighted,
        public ?string $onsetEarliestMean,
        public ?string $onsetLatestMean,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'site_id' => $this->siteId,
            'season_id' => $this->seasonId,
            'counted_rows' => $this->countedRows,
            'simple' => $this->simple,
            'weighted' => $this->weighted,
            'priority_adjusted' => $this->priorityAdjusted,
            'leading_simple' => $this->leadingSimple,
            'leading_weighted' => $this->leadingWeighted,
            'leading_priority' => $this->leadingPriority,
            'simple_weighted_agree' => $this->simpleWeightedAgree,
            'reconciliation' => $this->reconciliation,
            'exclusion_counts' => $this->exclusionCounts,
            'is_tie_simple' => $this->isTieSimple,
            'is_tie_weighted' => $this->isTieWeighted,
            'onset_earliest_mean' => $this->onsetEarliestMean,
            'onset_latest_mean' => $this->onsetLatestMean,
        ];
    }
}
