<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeasonSettings;
use Carbon\Carbon;

class TilkitRecordEngine
{
    public function __construct(
        private EligibilityGate $gate,
        private IndicatorHistoryService $history,
        private WeightingService $weighting,
        private WorkbookRecordService $workbook,
    ) {}

    public function recalculate(TilkitIndicatorRecord $record, ?TilkitSeasonSettings $settings = null): void
    {
        $record->refresh();
        $record->loadMissing(['indicator', 'workbookValues']);
        $settings ??= TilkitSeasonSettings::query()
            ->where('site_id', $record->site_id)
            ->where('season_id', $record->season_id)
            ->first();

        $hist = $this->history->forIndicator($record->indicator_id);
        $gate = $this->gate->evaluate($record);
        $weights = $this->weighting->compute(
            $record,
            $hist,
            $settings,
            $this->hazardFactor($settings),
        );

        $map = $this->workbook->rawWorkbookMap($record);
        $leadDays = $this->leadTimeDays($map);

        $derived = [
            'BL' => $hist->corroboration,
            'BM' => $hist->scoredSeasons,
            'BN' => $hist->hits,
            'BO' => $hist->misses,
            'BP' => $hist->falseStarts,
            'BQ' => $hist->hitRate() !== null ? round($hist->hitRate() * 100, 2) : null,
            'BT' => $weights->phase,
            'CA' => $weights->baseWeight,
            'CB' => $weights->finalWeight,
            'CC' => $weights->stabilityFlag ? 1 : 0,
            'CD' => 'OK',
            'CE' => $gate->tallyStatusLabel,
            'CF' => $weights->priorMultiplier,
            'CG' => $weights->tallyWeight,
            'CH' => $weights->tallyWeight,
            'DS' => $weights->priorityAdjustedWeight,
            'DT' => $this->gate->evaluateDuplicateMarker($record),
            'CJ' => $this->monthFromDate($map['CI'] ?? $map['O'] ?? null),
            'CM' => $this->calendarShift($map),
            'CU' => $leadDays,
            'CV' => $this->decisionHorizon($leadDays),
        ];

        $derived = array_filter($derived, fn ($v) => $v !== null && $v !== '');

        $this->workbook->storeDerivedValues($record, $derived);
    }

    private function hazardFactor(?TilkitSeasonSettings $settings): float
    {
        $hazards = $settings?->settings['hazard_priority'] ?? [];

        return (float) ($hazards['default'] ?? 1.0);
    }

    /** @param  array<string, mixed>  $map */
    private function leadTimeDays(array $map): ?int
    {
        $onset = $map['P'] ?? null;
        $sighted = $map['CI'] ?? $map['O'] ?? null;
        if (! $onset || ! $sighted) {
            return null;
        }
        try {
            $days = Carbon::parse($sighted)->diffInDays(Carbon::parse($onset), false);

            return max(0, (int) $days);
        } catch (\Throwable) {
            return null;
        }
    }

    private function decisionHorizon(?int $leadDays): ?string
    {
        if ($leadDays === null) {
            return null;
        }

        return match (true) {
            $leadDays >= 60 => 'Strategic',
            $leadDays >= 7 => 'Tactical',
            $leadDays >= 3 => 'Operational',
            default => 'Emergency',
        };
    }

    private function monthFromDate(mixed $date): ?int
    {
        if (! $date) {
            return null;
        }
        try {
            return (int) Carbon::parse($date)->format('n');
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param  array<string, mixed>  $map */
    private function calendarShift(array $map): ?int
    {
        $observed = $this->monthFromDate($map['CI'] ?? $map['O'] ?? null);
        $expected = isset($map['CL']) && is_numeric($map['CL']) ? (int) $map['CL'] : null;
        if ($observed === null || $expected === null) {
            return null;
        }

        return $observed - $expected;
    }
}
