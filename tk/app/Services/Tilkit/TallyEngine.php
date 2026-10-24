<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\TercileForecast;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\Tilkit\TilkitSite;
use App\Support\Tilkit\TallyResult;
use Illuminate\Support\Collection;

class TallyEngine
{
    public function __construct(
        private TilkitRecordEngine $recordEngine,
        private WorkbookRecordService $workbook,
        private EligibilityGate $gate,
        private TallyIntegrationService $integration,
    ) {}

    public function run(int $siteId, int $seasonId, ?TilkitSeasonSettings $settings = null): TallyResult
    {
        $site = TilkitSite::query()->findOrFail($siteId);
        $season = TilkitSeason::query()->findOrFail($seasonId);

        $records = TilkitIndicatorRecord::query()
            ->where('site_id', $siteId)
            ->where('season_id', $seasonId)
            ->with(['indicator', 'site', 'season', 'workbookValues'])
            ->get();

        $exclusionCounts = [];
        $counted = new Collection;
        $rowsLoggedWithId = 0;

        foreach ($records as $record) {
            if ($record->indicator?->indicator_uid) {
                $rowsLoggedWithId++;
            }
            $this->recordEngine->recalculate($record, $settings);
            $record->refresh()->load(['workbookValues', 'site', 'season', 'indicator']);
            $map = $this->workbook->rawWorkbookMap($record);
            if (config('tilkit.weather.engine.tally_reads_ce_only', true)) {
                $ce = $this->tallyStatusLabel($record);
                if (! EligibilityGate::ceCountsInTally($ce)) {
                    $key = EligibilityGate::exclusionKeyFromCeLabel($ce !== '' ? $ce : 'other');
                    $exclusionCounts[$key] = ($exclusionCounts[$key] ?? 0) + 1;

                    continue;
                }
            } else {
                $gate = $this->gate->evaluate($record, $site->name, $season->label, $map);
                if (! $gate->eligible) {
                    $key = $gate->exclusionReason ?? 'other';
                    $exclusionCounts[$key] = ($exclusionCounts[$key] ?? 0) + 1;

                    continue;
                }
            }
            $counted->push($record);
        }

        $simple = $this->simpleTally($counted);
        $weighted = $this->weightedTally($counted, 'CG');
        $priority = $this->weightedTally($counted, 'DS');

        $leadingSimple = $this->leadingTercile($simple['counts']);
        $leadingWeighted = $this->leadingTercile($weighted['counts']);
        $leadingPriority = $this->leadingTercile($priority['counts']);

        $reconciliation = $counted->count() === array_sum($simple['counts'])
            ? 'OK'
            : 'MISMATCH';

        $timingSummary = $this->timingSummary($counted);
        $onsetEarliest = $timingSummary['onset']['earliest_mean_simple'] ?? null;
        $onsetLatest = $timingSummary['onset']['latest_mean_simple'] ?? null;
        $distributionOutlook = $this->distributionOutlook($counted);

        $countedWithoutWeight = $counted->filter(fn (TilkitIndicatorRecord $r) => ($this->cellNumber($r, 'CG') ?? 0) <= 0)->count();

        $result = new TallyResult(
            siteId: $siteId,
            seasonId: $seasonId,
            countedRows: $counted->count(),
            simple: $simple['byTercile'],
            weighted: $weighted['byTercile'],
            priorityAdjusted: $priority['byTercile'],
            leadingSimple: $leadingSimple,
            leadingWeighted: $leadingWeighted,
            leadingPriority: $leadingPriority,
            simpleWeightedAgree: $leadingSimple !== null && $leadingSimple === $leadingWeighted,
            reconciliation: $reconciliation,
            exclusionCounts: $exclusionCounts,
            isTieSimple: $this->isTie($simple['counts']),
            isTieWeighted: $this->isTie($weighted['counts']),
            onsetEarliestMean: $onsetEarliest,
            onsetLatestMean: $onsetLatest,
            rowsLoggedWithId: $rowsLoggedWithId,
            countedWithoutWeight: $countedWithoutWeight,
            leadingSimpleLabel: $this->tercileLabel($leadingSimple, $simple['counts']),
            leadingWeightedLabel: $this->tercileLabel($leadingWeighted, $weighted['counts']),
            simpleWeightedAgreeLabel: ($leadingSimple !== null && $leadingSimple === $leadingWeighted) ? 'Yes' : 'No',
            timingSummary: $timingSummary,
            distributionOutlook: $distributionOutlook,
        );

        return $result->withIntegration($this->integration->enrich($result, $counted, $settings));
    }

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    private function simpleTally(Collection $counted): array
    {
        $counts = [1 => 0, 2 => 0, 3 => 0];
        foreach ($counted as $record) {
            $t = $record->tercile_forecast;
            if ($t instanceof TercileForecast && $t->isForecast()) {
                $counts[$t->value]++;
            }
        }
        $total = array_sum($counts);

        return [
            'counts' => $counts,
            'byTercile' => $this->percentages($counts, $total, 'count'),
        ];
    }

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    private function weightedTally(Collection $counted, string $weightColumn): array
    {
        $weights = [1 => 0.0, 2 => 0.0, 3 => 0.0];
        foreach ($counted as $record) {
            $t = $record->tercile_forecast;
            if (! ($t instanceof TercileForecast) || ! $t->isForecast()) {
                continue;
            }
            $w = (float) ($this->cellNumber($record, $weightColumn) ?? 0);
            $weights[$t->value] += $w;
        }
        $total = array_sum($weights);

        return [
            'counts' => $weights,
            'byTercile' => $this->percentages($weights, $total, 'weight'),
        ];
    }

    /** @param  array<int, int|float>  $counts */
    private function percentages(array $counts, float|int $total, string $mode): array
    {
        $out = [];
        foreach ([3, 2, 1] as $k) {
            $v = $counts[$k] ?? 0;
            $pct = $total > 0 ? round(($v / $total) * 100, 2) : 0.0;
            $out[$k] = $mode === 'count'
                ? ['count' => (int) $v, 'percent' => $pct]
                : ['weight' => (float) $v, 'percent' => $pct];
        }

        return $out;
    }

    /** @param  array<int, int|float>  $counts */
    private function leadingTercile(array $counts): ?int
    {
        if ($counts === []) {
            return null;
        }
        $max = max($counts);
        if ($max <= 0) {
            return null;
        }
        $leaders = array_keys(array_filter($counts, fn ($v) => $v === $max));

        return count($leaders) === 1 ? (int) $leaders[0] : null;
    }

    /** @param  array<int, int|float>  $counts */
    private function isTie(array $counts): bool
    {
        $max = max($counts);
        if ($max <= 0) {
            return false;
        }

        return count(array_filter($counts, fn ($v) => $v === $max)) > 1;
    }

    /** @param  array<int, int|float>  $counts */
    private function tercileLabel(?int $tercile, array $counts): string
    {
        if ($tercile === null) {
            return $this->isTie($counts) ? 'Tie' : '-';
        }

        return match ($tercile) {
            3 => 'Above normal (Average)',
            2 => 'Normal (Average)',
            1 => 'Below normal (Average)',
            default => '-',
        };
    }

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    private function timingSummary(Collection $counted): array
    {
        return [
            'onset' => $this->windowStats($counted, 'onset_earliest', 'onset_latest'),
            'cessation' => $this->windowStats($counted, 'cessation_earliest', 'cessation_latest'),
        ];
    }

    /**
     * @param  Collection<int, TilkitIndicatorRecord>  $counted
     * @return array<string, mixed>
     */
    private function windowStats(Collection $counted, string $earliestField, string $latestField): array
    {
        $earliestTs = [];
        $latestTs = [];
        $weightedEarliest = ['sum' => 0.0, 'weight' => 0.0];
        $weightedLatest = ['sum' => 0.0, 'weight' => 0.0];
        $singleEarliest = null;
        $singleLatest = null;
        $signsWithDates = 0;

        foreach ($counted as $record) {
            $weight = max(0.0, (float) ($this->cellNumber($record, 'CG') ?? 0));
            $hasDate = false;

            $earliest = $record->{$earliestField};
            if ($earliest !== null) {
                $ts = $earliest->timestamp;
                $earliestTs[] = $ts;
                $hasDate = true;
                if ($weight > 0) {
                    $weightedEarliest['sum'] += $ts * $weight;
                    $weightedEarliest['weight'] += $weight;
                }
                $singleEarliest = $singleEarliest === null ? $ts : min($singleEarliest, $ts);
            }

            $latest = $record->{$latestField};
            if ($latest !== null) {
                $ts = $latest->timestamp;
                $latestTs[] = $ts;
                $hasDate = true;
                if ($weight > 0) {
                    $weightedLatest['sum'] += $ts * $weight;
                    $weightedLatest['weight'] += $weight;
                }
                $singleLatest = $singleLatest === null ? $ts : max($singleLatest, $ts);
            }

            if ($hasDate) {
                $signsWithDates++;
            }
        }

        if ($earliestTs === []) {
            return [
                'earliest_mean_simple' => null,
                'earliest_mean_weighted' => null,
                'latest_mean_simple' => null,
                'latest_mean_weighted' => null,
                'single_earliest' => null,
                'single_latest' => null,
                'signs_with_dates' => 0,
            ];
        }

        return [
            'earliest_mean_simple' => $this->formatMeanTimestamp($earliestTs),
            'earliest_mean_weighted' => $this->formatWeightedMeanTimestamp($weightedEarliest),
            'latest_mean_simple' => $latestTs !== [] ? $this->formatMeanTimestamp($latestTs) : null,
            'latest_mean_weighted' => $latestTs !== [] ? $this->formatWeightedMeanTimestamp($weightedLatest) : null,
            'single_earliest' => $singleEarliest !== null ? date('Y-m-d', $singleEarliest) : null,
            'single_latest' => $singleLatest !== null ? date('Y-m-d', $singleLatest) : null,
            'signs_with_dates' => $signsWithDates,
        ];
    }

    /** @param  array<int, int>  $timestamps */
    private function formatMeanTimestamp(array $timestamps): string
    {
        return date('Y-m-d', (int) round(array_sum($timestamps) / count($timestamps)));
    }

    /** @param  array{sum: float, weight: float}  $weighted */
    private function formatWeightedMeanTimestamp(array $weighted): ?string
    {
        if ($weighted['weight'] <= 0) {
            return null;
        }

        return date('Y-m-d', (int) round($weighted['sum'] / $weighted['weight']));
    }

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    private function distributionOutlook(Collection $counted): ?array
    {
        if ($counted->isEmpty()) {
            return null;
        }

        $simpleCounts = [3 => 0, 2 => 0, 1 => 0, 0 => 0];
        $weightSums = [3 => 0.0, 2 => 0.0, 1 => 0.0, 0 => 0.0];

        foreach ($counted as $record) {
            $rating = $this->distributionRating($record);
            if ($rating === null) {
                continue;
            }
            $simpleCounts[$rating]++;
            $w = max(0.0, (float) ($this->cellNumber($record, 'CG') ?? 0));
            $weightSums[$rating] += $w;
        }

        $simpleTotal = array_sum($simpleCounts);
        $weightTotal = array_sum($weightSums);
        if ($simpleTotal === 0 && $weightTotal <= 0) {
            return null;
        }

        $simple = $this->distributionPercentages($simpleCounts, $simpleTotal, 'count');
        $weighted = $this->distributionPercentages($weightSums, $weightTotal, 'weight');

        return [
            'simple' => $simple,
            'weighted' => $weighted,
            'leading_simple' => $this->leadingDistribution($simpleCounts),
            'leading_weighted' => $this->leadingDistribution($weightSums),
        ];
    }

    private function distributionRating(TilkitIndicatorRecord $record): ?int
    {
        if ($record->distribution_forecast !== null && is_numeric($record->distribution_forecast)) {
            $v = (int) $record->distribution_forecast;

            return in_array($v, [0, 1, 2, 3], true) ? $v : null;
        }

        $cell = $record->workbookValues->firstWhere('column_letter', 'T');
        if ($cell?->value_number === null) {
            return null;
        }
        $v = (int) $cell->value_number;

        return in_array($v, [0, 1, 2, 3], true) ? $v : null;
    }

    /** @param  array<int, int|float>  $counts */
    private function distributionPercentages(array $counts, float|int $total, string $mode): array
    {
        $out = [];
        foreach ([3, 2, 1, 0] as $k) {
            $v = $counts[$k] ?? 0;
            $pct = $total > 0 ? round(($v / $total) * 100, 2) : 0.0;
            $out[$k] = $mode === 'count'
                ? ['count' => (int) $v, 'percent' => $pct]
                : ['weight' => (float) $v, 'percent' => $pct];
        }

        return $out;
    }

    /** @param  array<int, int|float>  $counts */
    private function leadingDistribution(array $counts): ?int
    {
        if ($counts === []) {
            return null;
        }
        $max = max($counts);
        if ($max <= 0) {
            return null;
        }
        $leaders = array_keys(array_filter($counts, fn ($v) => $v === $max));

        return count($leaders) === 1 ? (int) $leaders[0] : null;
    }

    private function cellNumber(TilkitIndicatorRecord $record, string $letter): ?float
    {
        $cell = $record->workbookValues->firstWhere('column_letter', $letter);

        return $cell?->value_number !== null ? (float) $cell->value_number : null;
    }

    private function tallyStatusLabel(TilkitIndicatorRecord $record): string
    {
        $record->loadMissing('workbookValues');
        $cell = $record->workbookValues->firstWhere('column_letter', 'CE');

        return trim((string) ($cell?->value_text ?? ''));
    }
}
