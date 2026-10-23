<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\SeasonOutcome;
use App\Enums\Tilkit\TercileForecast;
use App\Models\Tilkit\TilkitIndicatorRecord;

class SeasonOutcomeScoringService
{
    public function scoreRecord(TilkitIndicatorRecord $record): ?SeasonOutcome
    {
        $predicted = $record->tercile_forecast;
        $actual = $record->actual_tercile;
        if (! ($predicted instanceof TercileForecast) || ! $predicted->isForecast() || $actual === null) {
            return null;
        }

        if ((int) $actual === $predicted->value) {
            return SeasonOutcome::Hit;
        }

        $map = app(WorkbookRecordService::class)->rawWorkbookMap($record);
        $notes = strtolower((string) ($map['BA'] ?? ''));
        if (str_contains($notes, 'false start')) {
            return SeasonOutcome::FalseStart;
        }

        return SeasonOutcome::Miss;
    }

    public function persistOutcome(TilkitIndicatorRecord $record): void
    {
        $outcome = $this->scoreRecord($record);
        if ($outcome === null) {
            return;
        }
        $record->season_outcome = $outcome;
        $record->save();
        app(WorkbookRecordService::class)->storeRawValues($record, [
            'AZ' => match ($outcome) {
                SeasonOutcome::Hit => 1,
                SeasonOutcome::Miss => 2,
                SeasonOutcome::FalseStart => 3,
            },
        ]);
        app(TilkitRecordEngine::class)->recalculate($record);
    }
}
