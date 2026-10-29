<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\ValidationStatus;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\User;
use App\Support\Tilkit\PilotSeasonCalendar;
use App\Support\Tilkit\WorkbookColumnRegistry;
use Illuminate\Support\Facades\DB;

class WorkbookDemoImporter
{
    public function __construct(
        private WorkbookRecordService $workbook,
    ) {}

    /** @param  list<array<string, mixed>>  $rows */
    public function importRows(DcProject $tool, array $rows, ?TilkitProgram $program = null, ?User $enteredBy = null): array
    {
        return DB::transaction(function () use ($tool, $rows, $program, $enteredBy): array {
            $sites = [];
            $seasons = [];
            $indicators = [];
            $count = 0;

            $program ??= app(TilkitProgramContext::class)->ensureDefault($tool);

            foreach ($rows as $row) {
                unset($row['_excel_row']);
                $siteName = (string) ($row['AI'] ?? 'Unknown site');
                $seasonLabel = (string) ($row['AM'] ?? 'Unknown season');
                $uid = (string) ($row['AH'] ?? '');

                $countryName = (string) ($row['AL'] ?? 'Kenya');
                [$countryCode, $continent] = $this->countryMeta($countryName);

                $site = $sites[$siteName] ??= TilkitSite::query()->firstOrCreate(
                    ['program_id' => $program->id, 'name' => $siteName],
                    [
                        'project_id' => $tool->id,
                        'ward' => ($row['AJ'] ?? null) ? (string) $row['AJ'] : null,
                        'county' => ($row['AK'] ?? null) ? (string) $row['AK'] : null,
                        'country' => $countryCode,
                        'country_name' => $countryName,
                        'continent' => $continent,
                        'primary_language' => isset($row['AO']) ? (string) $row['AO'] : null,
                        'latitude' => $this->latitudeFromRow($row),
                        'longitude' => $this->longitudeFromRow($row),
                    ],
                );
                $seasonKey = $program->id.'|'.$site->id.'|'.$seasonLabel;
                $season = $seasons[$seasonKey] ??= $this->resolveSeason($tool, $program, $site, $seasonLabel);

                $indicator = $indicators[$uid] ??= $this->resolveIndicator($tool, $uid, $row);

                $record = TilkitIndicatorRecord::query()->firstOrCreate(
                    [
                        'indicator_id' => $indicator->id,
                        'site_id' => $site->id,
                        'season_id' => $season->id,
                    ]
                );

                $raw = [];
                foreach ($row as $letter => $value) {
                    if (! preg_match('/^[A-Z]{1,2}$/', $letter)) {
                        continue;
                    }
                    $meta = WorkbookColumnRegistry::forColumn($letter);
                    if ($meta === null || $meta['derived']) {
                        continue;
                    }
                    if ($letter === 'AP') {
                        $value = $this->mapValidationLabel($value);
                    }
                    $raw[$letter] = $value;
                }

                $this->workbook->storeRawValues($record, $raw);

                $recordMeta = [
                    'collection_year' => $this->collectionYearFromSeasonLabel($seasonLabel),
                ];
                if (isset($raw['AN']) && $raw['AN'] !== '') {
                    $recordMeta['collection_date'] = $raw['AN'];
                }
                $record->forceFill($recordMeta)->save();

                $this->workbook->storeReferenceColumns($record->fresh());

                if ($enteredBy !== null) {
                    $record->forceFill(['entered_by' => $enteredBy->id])->save();
                }

                $count++;
            }

            return [
                'records' => $count,
                'sites' => count($sites),
                'seasons' => count($seasons),
            ];
        });
    }

    /** @return array{records: int, sites: int, seasons: int} */
    public function importFixture(DcProject $tool, string $fixturePath): array
    {
        $payload = json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);

        return $this->importRows($tool, $payload['rows'] ?? []);
    }

    /** @param  array<string, mixed>  $row */
    private function resolveIndicator(DcProject $tool, string $uid, array $row): TilkitIndicator
    {
        $existing = TilkitIndicator::query()->where('indicator_uid', $uid)->first();
        if ($existing) {
            return $existing;
        }

        $prefix = implode('-', array_slice(explode('-', $uid), 0, 3));
        $code = TilkitIndicatorCode::query()->where('code', $prefix)->first();
        if ($code === null) {
            $parts = explode('-', $prefix);
            $code = TilkitIndicatorCode::query()->create([
                'code' => $prefix,
                'category' => $parts[0] ?? '01',
                'type' => $parts[1] ?? null,
                'sub_type' => $parts[2] ?? null,
                'protocol_version' => config('tilkit.weather.protocol_version'),
            ]);
        }

        return TilkitIndicator::query()->create([
            'indicator_uid' => $uid,
            'indicator_code_id' => $code->id,
            'project_id' => $tool->id,
            'local_name' => (string) ($row['C'] ?? $row['D'] ?? $uid),
        ]);
    }

    private function resolveSeason(DcProject $tool, TilkitProgram $program, TilkitSite $site, string $seasonLabel): TilkitSeason
    {
        $season = TilkitSeason::query()
            ->where('program_id', $program->id)
            ->where('site_id', $site->id)
            ->where('label', $seasonLabel)
            ->first();

        if ($season === null) {
            $season = TilkitSeason::query()
                ->where('project_id', $tool->id)
                ->where('site_id', $site->id)
                ->where('label', $seasonLabel)
                ->first();
        }

        $dates = PilotSeasonCalendar::datesForWorkbookSeasonLabel($seasonLabel) ?? [
            'starts_on' => str_contains(strtoupper($seasonLabel), 'OND') ? '2026-10-01' : '2026-03-01',
            'ends_on' => str_contains(strtoupper($seasonLabel), 'OND') ? '2027-02-28' : '2026-05-31',
            'start_month' => str_contains(strtoupper($seasonLabel), 'OND') ? 10 : 3,
            'end_month' => str_contains(strtoupper($seasonLabel), 'OND') ? 12 : 5,
        ];

        if ($season === null) {
            return TilkitSeason::query()->create([
                'project_id' => $tool->id,
                'program_id' => $program->id,
                'site_id' => $site->id,
                'label' => $seasonLabel,
                'protocol_version' => config('tilkit.weather.protocol_version'),
                ...$dates,
            ]);
        }

        $season->forceFill([
            'program_id' => $program->id,
            'site_id' => $site->id,
            'protocol_version' => $season->protocol_version ?: config('tilkit.weather.protocol_version'),
            ...$dates,
        ])->save();

        return $season;
    }

    /** @return array{0: string, 1: string} ISO2 and continent */
    private function countryMeta(string $countryName): array
    {
        $normalized = strtolower(trim($countryName));

        return match (true) {
            str_contains($normalized, 'ghana') => ['GH', 'Africa'],
            str_contains($normalized, 'kenya') => ['KE', 'Africa'],
            default => ['KE', 'Africa'],
        };
    }

    /** @param  array<string, mixed>  $row */
    private function latitudeFromRow(array $row): ?float
    {
        $gps = isset($row['Z']) ? (string) $row['Z'] : '';
        if ($gps === '' || ! str_contains($gps, ',')) {
            return null;
        }
        $parts = array_map('trim', explode(',', $gps, 2));

        return is_numeric($parts[0] ?? null) ? (float) $parts[0] : null;
    }

    /** @param  array<string, mixed>  $row */
    private function longitudeFromRow(array $row): ?float
    {
        $gps = isset($row['Z']) ? (string) $row['Z'] : '';
        if ($gps === '' || ! str_contains($gps, ',')) {
            return null;
        }
        $parts = array_map('trim', explode(',', $gps, 2));

        return is_numeric($parts[1] ?? null) ? (float) $parts[1] : null;
    }

    private function collectionYearFromSeasonLabel(string $seasonLabel): int
    {
        if (preg_match('/\b(20\d{2})\b/', $seasonLabel, $matches)) {
            return (int) $matches[1];
        }

        return 2026;
    }

    private function mapValidationLabel(mixed $value): string
    {
        $v = strtolower(str_replace(['/', ' '], [' ', '_'], (string) $value));

        return match (true) {
            str_contains($v, 'contest') => ValidationStatus::Contested->value,
            str_contains($v, 'plenary') => ValidationStatus::CommunityPlenaryEndorsed->value,
            str_contains($v, 'sub') => ValidationStatus::SubCommunitySpecific->value,
            str_contains($v, 'provisional') => ValidationStatus::ProvisionalEmerging->value,
            str_contains($v, 'fgd') => ValidationStatus::FgdValidated->value,
            default => ValidationStatus::FgdValidated->value,
        };
    }
}
