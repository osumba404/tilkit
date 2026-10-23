<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\ReliabilityRating;
use App\Enums\Tilkit\SeasonOutcome;
use App\Enums\Tilkit\TercileForecast;
use App\Enums\Tilkit\ValidationStatus;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitWorkbookValue;
use App\Support\Tilkit\WorkbookColumnRegistry;
use App\Support\Tilkit\WorkbookValueNormalizer;
use Illuminate\Support\Facades\DB;

class WorkbookRecordService
{
    /** @param  array<string, mixed>  $workbook  Keys: column letters (A, N, AA) or field keys */
    public function storeRawValues(TilkitIndicatorRecord $record, array $workbook): TilkitIndicatorRecord
    {
        return DB::transaction(function () use ($record, $workbook): TilkitIndicatorRecord {
            foreach ($workbook as $key => $value) {
                $letter = $this->resolveColumnLetter($key);
                $meta = WorkbookColumnRegistry::assertRawColumn($letter);
                $kind = WorkbookColumnRegistry::inputKind($meta);
                $normalized = WorkbookValueNormalizer::normalize($value, $kind);

                TilkitWorkbookValue::query()->updateOrCreate(
                    [
                        'indicator_record_id' => $record->id,
                        'column_letter' => $letter,
                    ],
                    [
                        'field_key' => $meta['key'],
                        'is_derived' => false,
                        'protocol_version' => $record->protocol_version,
                        ...$normalized,
                    ]
                );
            }

            $this->syncCanonicalFromWorkbook($record->fresh(['workbookValues']));

            return $record->fresh(['workbookValues']);
        });
    }

    /** @return array<string, mixed> Column letter => display value */
    public function rawWorkbookMap(TilkitIndicatorRecord $record): array
    {
        $record->loadMissing('workbookValues');
        $map = [];
        foreach ($record->workbookValues->where('is_derived', false) as $cell) {
            $meta = WorkbookColumnRegistry::forColumn($cell->column_letter);
            $kind = $meta ? WorkbookColumnRegistry::inputKind($meta) : 'text';
            $map[$cell->column_letter] = WorkbookValueNormalizer::display(
                $cell->value_number !== null ? (float) $cell->value_number : null,
                $cell->value_text,
                $cell->value_date?->format('Y-m-d'),
                $cell->value_json,
                $kind,
            );
        }

        return $map;
    }

    /** @param  array<string, mixed>  $derived  Engine output only */
    public function storeDerivedValues(TilkitIndicatorRecord $record, array $derived): void
    {
        DB::transaction(function () use ($record, $derived): void {
            foreach ($derived as $letter => $value) {
                $letter = $this->resolveColumnLetter($letter);
                $meta = WorkbookColumnRegistry::forColumn($letter);
                if ($meta === null || ! $meta['derived']) {
                    continue;
                }
                $kind = WorkbookColumnRegistry::inputKind($meta);
                $normalized = WorkbookValueNormalizer::normalize($value, $kind);

                TilkitWorkbookValue::query()->updateOrCreate(
                    [
                        'indicator_record_id' => $record->id,
                        'column_letter' => $letter,
                    ],
                    [
                        'field_key' => $meta['key'],
                        'is_derived' => true,
                        'protocol_version' => $record->protocol_version,
                        ...$normalized,
                    ]
                );
            }
        });
    }

    public function syncCanonicalFromWorkbook(TilkitIndicatorRecord $record): void
    {
        $map = $this->rawWorkbookMap($record);

        $record->sighted = (int) ($map['N'] ?? $record->sighted) === 1;
        $record->confirmed = (int) ($map['AC'] ?? $record->confirmed) === 1;
        $record->approved = (int) ($map['AD'] ?? $record->approved) === 1;
        $record->verified = (int) ($map['AE'] ?? $record->verified) === 1;

        if (isset($map['AP'])) {
            $record->validation_status = $this->mapValidationStatus($map['AP']);
        }

        if (isset($map['U']) && is_numeric($map['U'])) {
            $record->tercile_forecast = TercileForecast::from((int) $map['U']);
        }
        if (isset($map['M']) && is_numeric($map['M'])) {
            $record->current_reliability = ReliabilityRating::from((int) $map['M']);
        }
        if (isset($map['V']) && is_numeric($map['V'])) {
            $record->decision_relevance = ReliabilityRating::from((int) $map['V']);
        }

        $record->onset_earliest = $map['P'] ?? $record->onset_earliest;
        $record->onset_latest = $map['Q'] ?? $record->onset_latest;
        $record->cessation_earliest = $map['R'] ?? $record->cessation_earliest;
        $record->cessation_latest = $map['S'] ?? $record->cessation_latest;
        if (isset($map['T']) && is_numeric($map['T'])) {
            $record->distribution_forecast = (int) $map['T'];
        }

        $record->actual_onset = $map['AS'] ?? $record->actual_onset;
        $record->actual_cessation = $map['AT'] ?? $record->actual_cessation;
        if (isset($map['AZ'])) {
            $record->season_outcome = $this->mapOutcome($map['AZ']);
        }

        if (isset($map['BB'])) {
            $record->do_no_harm = (int) $map['BB'] === 1;
        }
        if (isset($map['BC']) && is_string($map['BC'])) {
            $this->parseConsent($map['BC'], $record);
        }

        if (isset($map['BG']) || isset($map['BH'])) {
            $record->demographics = [
                'observers_count' => $map['BG'] ?? null,
                'gender_mix' => $map['BH'] ?? null,
                'age_generation' => $map['BI'] ?? null,
                'livelihood_mix' => $map['BJ'] ?? null,
                'disability_inclusion' => $map['BK'] ?? null,
            ];
        }

        $record->save();
    }

    /** Persist Excel reference cells tied to the record’s site, season, and indicator. */
    public function storeReferenceColumns(TilkitIndicatorRecord $record): void
    {
        $record->loadMissing(['indicator', 'site', 'season']);

        $this->storeRawValues($record, array_filter([
            'AH' => $record->indicator?->indicator_uid,
            'AI' => $record->site?->name,
            'AM' => $record->season?->label,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function seedWorkbookFromCanonical(TilkitIndicatorRecord $record): void
    {
        $payload = [
            'N' => $record->sighted ? 1 : 0,
            'AC' => $record->confirmed ? 1 : 0,
            'AD' => $record->approved ? 1 : 0,
            'AE' => $record->verified ? 1 : 0,
            'AP' => $record->validation_status?->value,
            'U' => $record->tercile_forecast?->value,
            'M' => $record->current_reliability?->value,
            'V' => $record->decision_relevance?->value,
            'P' => $record->onset_earliest?->format('Y-m-d'),
            'Q' => $record->onset_latest?->format('Y-m-d'),
            'R' => $record->cessation_earliest?->format('Y-m-d'),
            'S' => $record->cessation_latest?->format('Y-m-d'),
            'T' => $record->distribution_forecast,
            'AS' => $record->actual_onset?->format('Y-m-d'),
            'AT' => $record->actual_cessation?->format('Y-m-d'),
            'AZ' => $record->season_outcome?->value,
            'AH' => $record->indicator?->indicator_uid,
            'AI' => $record->site?->name,
            'AM' => $record->season?->label,
        ];

        $this->storeRawValues($record, array_filter($payload, fn ($v) => $v !== null && $v !== ''));
    }

    private function resolveColumnLetter(string $key): string
    {
        $key = trim($key);
        if (preg_match('/^[A-Z]{1,2}$/i', $key)) {
            return strtoupper($key);
        }

        foreach (WorkbookColumnRegistry::rawColumns() as $col) {
            if ($col['key'] === $key) {
                return $col['column'];
            }
        }

        throw new \InvalidArgumentException("Cannot resolve workbook column for key {$key}.");
    }

    private function mapValidationStatus(mixed $value): ValidationStatus
    {
        $v = strtolower(str_replace(' ', '_', (string) $value));

        return match (true) {
            str_contains($v, 'contest') => ValidationStatus::Contested,
            str_contains($v, 'plenary') => ValidationStatus::CommunityPlenaryEndorsed,
            str_contains($v, 'sub') => ValidationStatus::SubCommunitySpecific,
            str_contains($v, 'provisional') => ValidationStatus::ProvisionalEmerging,
            default => ValidationStatus::FgdValidated,
        };
    }

    private function mapOutcome(mixed $value): ?SeasonOutcome
    {
        $v = strtolower((string) $value);

        return match (true) {
            str_contains($v, 'false') => SeasonOutcome::FalseStart,
            str_contains($v, 'miss') => SeasonOutcome::Miss,
            str_contains($v, 'hit') => SeasonOutcome::Hit,
            default => null,
        };
    }

    private function parseConsent(string $value, TilkitIndicatorRecord $record): void
    {
        $parts = array_map('trim', explode(';', $value));
        if (isset($parts[0]) && is_numeric($parts[0])) {
            $record->consent_level = (int) $parts[0];
        }
        if (count($parts) >= 3) {
            $record->consent_credit = $parts[2];
        }
    }
}
