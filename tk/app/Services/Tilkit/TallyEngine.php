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
            $gate = $this->gate->evaluate($record, $site->name, $season->label, $map);
            if (! $gate->eligible) {
                $key = $gate->exclusionReason ?? 'other';
                $exclusionCounts[$key] = ($exclusionCounts[$key] ?? 0) + 1;

                continue;
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

        [$onsetEarliest, $onsetLatest] = $this->meanOnsetWindow($counted);

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
            return $this->isTie($counts) ? 'Tie' : '—';
        }

        return match ($tercile) {
            3 => 'Above normal (Average)',
            2 => 'Normal (Average)',
            1 => 'Below normal (Average)',
            default => '—',
        };
    }

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    private function meanOnsetWindow(Collection $counted): array
    {
        $earliest = [];
        $latest = [];
        foreach ($counted as $record) {
            if ($record->onset_earliest) {
                $earliest[] = $record->onset_earliest->timestamp;
            }
            if ($record->onset_latest) {
                $latest[] = $record->onset_latest->timestamp;
            }
        }
        if ($earliest === []) {
            return [null, null];
        }

        return [
            date('Y-m-d', (int) round(array_sum($earliest) / count($earliest))),
            $latest !== [] ? date('Y-m-d', (int) round(array_sum($latest) / count($latest))) : null,
        ];
    }

    private function cellNumber(TilkitIndicatorRecord $record, string $letter): ?float
    {
        $cell = $record->workbookValues->firstWhere('column_letter', $letter);

        return $cell?->value_number !== null ? (float) $cell->value_number : null;
    }
}
