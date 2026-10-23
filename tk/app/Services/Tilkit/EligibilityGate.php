<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\TercileForecast;
use App\Enums\Tilkit\ValidationStatus;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Support\Tilkit\GateResult;

/**
 * v4 eligibility gate (Developer Brief §5).
 */
class EligibilityGate
{
    /** @param  array<string, mixed>|null  $workbookMap */
    public function evaluate(
        TilkitIndicatorRecord $record,
        ?string $expectedSiteName = null,
        ?string $expectedSeasonLabel = null,
        ?array $workbookMap = null,
    ): GateResult {
        if ($expectedSiteName !== null && $workbookMap !== null) {
            $ai = isset($workbookMap['AI']) ? trim((string) $workbookMap['AI']) : '';
            if ($ai !== '' && strcasecmp($ai, $expectedSiteName) !== 0) {
                return GateResult::excluded('other_site_season', 'Other site/ season (AI/AM mismatch)');
            }
        }
        if ($expectedSeasonLabel !== null && $workbookMap !== null) {
            $am = isset($workbookMap['AM']) ? trim((string) $workbookMap['AM']) : '';
            if ($am !== '' && strcasecmp($am, $expectedSeasonLabel) !== 0) {
                return GateResult::excluded('other_site_season', 'Other site/ season (AI/AM mismatch)');
            }
        }

        if ($record->indicator?->indicator_uid === null || $record->indicator->indicator_uid === '') {
            return GateResult::excluded('missing_indicator_uid', 'Missing Unique Indicator ID (AH)');
        }

        if (! $record->sighted) {
            return GateResult::excluded('not_sighted', 'Not sighted (N)');
        }

        if (! $record->confirmed) {
            return GateResult::excluded('not_confirmed', 'Not confirmed (AC)');
        }

        if (! $record->approved) {
            return GateResult::excluded('not_approved', 'Not approved (AD)');
        }

        $status = $record->validation_status;
        if ($status instanceof ValidationStatus && $status->excludedFromTally()) {
            return GateResult::excluded('contested', 'Contested (AP)');
        }

        $tercile = $record->tercile_forecast;
        if (! $tercile instanceof TercileForecast || ! $tercile->isForecast()) {
            return GateResult::excluded('invalid_tercile', 'No valid tercile forecast (U)');
        }

        return GateResult::eligible('Counted in tally');
    }

    public function evaluateDuplicateMarker(TilkitIndicatorRecord $record): int
    {
        return 1;
    }
}
