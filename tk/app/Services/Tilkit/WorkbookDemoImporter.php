<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\ValidationStatus;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Support\Tilkit\WorkbookColumnRegistry;
use Illuminate\Support\Facades\DB;

class WorkbookDemoImporter
{
    public function __construct(
        private WorkbookRecordService $workbook,
    ) {}

    /** @return array{records: int, sites: int, seasons: int} */
    public function importFixture(DcProject $tool, string $fixturePath): array
    {
        $payload = json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
        $rows = $payload['rows'] ?? [];

        return DB::transaction(function () use ($tool, $rows): array {
            $sites = [];
            $seasons = [];
            $indicators = [];
            $count = 0;

            foreach ($rows as $row) {
                unset($row['_excel_row']);
                $siteName = (string) ($row['AI'] ?? 'Unknown site');
                $seasonLabel = (string) ($row['AM'] ?? 'Unknown season');
                $uid = (string) ($row['AH'] ?? '');

                $site = $sites[$siteName] ??= TilkitSite::query()->firstOrCreate(
                    ['project_id' => $tool->id, 'name' => $siteName],
                    ['country' => 'KE']
                );
                $season = $seasons[$seasonLabel] ??= TilkitSeason::query()->firstOrCreate(
                    ['project_id' => $tool->id, 'label' => $seasonLabel],
                    ['protocol_version' => config('tilkit.weather.protocol_version')]
                );

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
                $count++;
            }

            return [
                'records' => $count,
                'sites' => count($sites),
                'seasons' => count($seasons),
            ];
        });
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
