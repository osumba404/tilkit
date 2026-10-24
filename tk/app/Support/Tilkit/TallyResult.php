<?php

namespace App\Support\Tilkit;

readonly class TallyResult
{
    /** @param  array<int, array{count: int, percent: float}>  $simple  Keys 1|2|3 */
    /** @param  array<int, array{weight: float, percent: float}>  $weighted */
    /** @param  array<int, array{weight: float, percent: float}>  $priorityAdjusted */
    /** @param  array<string, int>  $exclusionCounts */
    /** @param  array<string, mixed>  $timingSummary */
    /** @param  array<string, mixed>|null  $distributionOutlook */
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
        public int $rowsLoggedWithId,
        public int $countedWithoutWeight,
        public string $leadingSimpleLabel,
        public string $leadingWeightedLabel,
        public string $simpleWeightedAgreeLabel,
        public array $integration = [],
        public array $timingSummary = [],
        public ?array $distributionOutlook = null,
    ) {}

    /** @param  array<string, mixed>  $integration */
    public function withIntegration(array $integration): self
    {
        return new self(
            siteId: $this->siteId,
            seasonId: $this->seasonId,
            countedRows: $this->countedRows,
            simple: $this->simple,
            weighted: $this->weighted,
            priorityAdjusted: $this->priorityAdjusted,
            leadingSimple: $this->leadingSimple,
            leadingWeighted: $this->leadingWeighted,
            leadingPriority: $this->leadingPriority,
            simpleWeightedAgree: $this->simpleWeightedAgree,
            reconciliation: $this->reconciliation,
            exclusionCounts: $this->exclusionCounts,
            isTieSimple: $this->isTieSimple,
            isTieWeighted: $this->isTieWeighted,
            onsetEarliestMean: $this->onsetEarliestMean,
            onsetLatestMean: $this->onsetLatestMean,
            rowsLoggedWithId: $this->rowsLoggedWithId,
            countedWithoutWeight: $this->countedWithoutWeight,
            leadingSimpleLabel: $this->leadingSimpleLabel,
            leadingWeightedLabel: $this->leadingWeightedLabel,
            simpleWeightedAgreeLabel: $this->simpleWeightedAgreeLabel,
            integration: $integration,
            timingSummary: $this->timingSummary,
            distributionOutlook: $this->distributionOutlook,
        );
    }

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
            'rows_logged_with_id' => $this->rowsLoggedWithId,
            'counted_without_weight' => $this->countedWithoutWeight,
            'leading_simple_label' => $this->leadingSimpleLabel,
            'leading_weighted_label' => $this->leadingWeightedLabel,
            'simple_weighted_agree_label' => $this->simpleWeightedAgreeLabel,
            'integration' => $this->integration,
            'timing_summary' => $this->timingSummary,
            'distribution_outlook' => $this->distributionOutlook,
        ];
    }
}
