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

        if (! $this->isFirstRecord($record)) {
            return GateResult::excluded(
                'duplicate',
                'Excluded: duplicate (later row with same ID, site and season)',
            );
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

        if (app(ClimateWeightReviewService::class)->hasPendingReview($record)) {
            return GateResult::excluded('climate_review_pending', 'Climate-change weight review pending (CP STRONG)');
        }

        return GateResult::eligible('Counted in tally');
    }

    public function evaluateDuplicateMarker(TilkitIndicatorRecord $record): int
    {
        return $this->isFirstRecord($record) ? 1 : 0;
    }

    public function isFirstRecord(TilkitIndicatorRecord $record): bool
    {
        if ($record->indicator_id === null || $record->site_id === null || $record->season_id === null) {
            return true;
        }

        if ($record->id === null) {
            return true;
        }

        return ! TilkitIndicatorRecord::query()
            ->where('indicator_id', $record->indicator_id)
            ->where('site_id', $record->site_id)
            ->where('season_id', $record->season_id)
            ->where('id', '<', $record->id)
            ->exists();
    }

    public static function ceCountsInTally(?string $ceLabel): bool
    {
        $ce = trim((string) $ceLabel);

        return $ce !== '' && str_starts_with($ce, 'Counted');
    }

    public static function exclusionKeyFromCeLabel(string $ceLabel): string
    {
        $ce = strtolower($ceLabel);
        if (str_contains($ce, 'duplicate')) {
            return 'duplicate';
        }
        if (str_contains($ce, 'not sighted')) {
            return 'not_sighted';
        }
        if (str_contains($ce, 'not confirmed')) {
            return 'not_confirmed';
        }
        if (str_contains($ce, 'not approved')) {
            return 'not_approved';
        }
        if (str_contains($ce, 'contested')) {
            return 'contested';
        }
        if (str_contains($ce, 'tercile') || str_contains($ce, 'no valid')) {
            return 'invalid_tercile';
        }
        if (str_contains($ce, 'other site') || str_contains($ce, 'season')) {
            return 'other_site_season';
        }
        if (str_contains($ce, 'climate')) {
            return 'climate_review_pending';
        }
        if (str_contains($ce, 'missing') && str_contains($ce, 'id')) {
            return 'missing_indicator_uid';
        }

        return 'other';
    }
}
