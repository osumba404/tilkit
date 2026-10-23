<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\TercileForecast;
use App\Enums\Tilkit\ValidationStatus;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Support\Tilkit\GateResult;

/**
 * v4 eligibility gate (Developer Brief §5). Weighting and tally are Phase 2.
 */
class EligibilityGate
{
    public function evaluate(TilkitIndicatorRecord $record): GateResult
    {
        if ($record->indicator?->indicator_uid === null || $record->indicator->indicator_uid === '') {
            return GateResult::excluded('missing_indicator_uid');
        }

        if (! $record->sighted) {
            return GateResult::excluded('not_sighted');
        }

        if (! $record->confirmed) {
            return GateResult::excluded('not_confirmed');
        }

        if (! $record->approved) {
            return GateResult::excluded('not_approved');
        }

        $status = $record->validation_status;
        if ($status instanceof ValidationStatus && $status->excludedFromTally()) {
            return GateResult::excluded('contested');
        }

        $tercile = $record->tercile_forecast;
        if (! $tercile instanceof TercileForecast || ! $tercile->isForecast()) {
            return GateResult::excluded('invalid_tercile');
        }

        return GateResult::eligible();
    }
}
