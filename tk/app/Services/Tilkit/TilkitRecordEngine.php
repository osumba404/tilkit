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
        private ConfidenceScoreCalculator $confidence,
        private V4FieldDeriver $v4,
        private ClimateWeightReviewService $climateReviews,
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
        $map = $this->workbook->rawWorkbookMap($record);
        $gate = $this->gate->evaluate(
            $record,
            $record->site?->name,
            $record->season?->label,
            $map,
        );
        $hazardFactor = $this->v4->hazardPriorityFactor($map, $settings);
        $weights = $this->weighting->compute(
            $record,
            $hist,
            $settings,
            $hazardFactor,
        );

        $leadDays = $this->leadTimeDays($map);
        $kmsaAgreement = $this->v4->kmsaAgreement(
            $record->tercile_forecast?->isForecast() ? $record->tercile_forecast->value : null,
            $map,
            $settings,
        );
        $map['DK'] = $kmsaAgreement;
        $dq = $this->confidence->score($record, $hist, $map, $weights->baseWeight, $weights->stabilityFlag, $settings);

        $vci = $settings?->ndma_vci3m ?? (isset($map['CR']) && is_numeric($map['CR']) ? (float) $map['CR'] : null);
        $cs = $this->v4->vegetationClassFromVci($vci);
        $cq = isset($map['CQ']) && is_numeric($map['CQ']) ? (int) $map['CQ'] : null;
        $cp = $this->v4->climateChangeFlag($map, $hist, $weights->stabilityFlag, $hist->hitRate());

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
            'DR' => $hazardFactor,
            'DS' => $weights->priorityAdjustedWeight,
            'DT' => $this->gate->evaluateDuplicateMarker($record),
            'CJ' => $this->monthFromDate($map['CI'] ?? $map['O'] ?? null),
            'CM' => $this->calendarShift($map),
            'CP' => $cp,
            'CS' => $cs,
            'CT' => $this->v4->vegetationCheck($cq, $cs),
            'CU' => $leadDays,
            'CV' => $this->decisionHorizon($leadDays),
            'DI' => $this->v4->inclusionShare($map),
            'DH' => $this->v4->warningLeadDays($map),
            'DK' => $kmsaAgreement,
            'DQ' => $dq,
        ];

        $derived = array_filter($derived, fn ($v) => $v !== null && $v !== '');

        $this->workbook->storeDerivedValues($record, $derived);
        $this->climateReviews->syncFromDerivedFlag($record, $cp);
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
