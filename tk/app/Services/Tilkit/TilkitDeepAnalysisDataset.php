<?php

namespace App\Services\Tilkit;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Support\Tilkit\WorkbookColumnRegistry;
use App\Support\Tilkit\WorkbookInputSpec;

class TilkitDeepAnalysisDataset
{
    public function __construct(
        private WorkbookRecordService $workbook,
        private TilkitConsentGate $consent,
    ) {}

    /** @return array{columns: list<array<string, mixed>>, filters: array<string, mixed>} */
    public function schema(DcProject $tool): array
    {
        $columns = [
            ['id' => '_record_id', 'label' => 'Record ID', 'field' => 'record_id', 'kind' => 'integer', 'group' => 'Meta'],
            ['id' => '_indicator_uid', 'label' => 'Indicator UID', 'field' => 'indicator_uid', 'kind' => 'text', 'group' => 'Meta'],
            ['id' => '_site', 'label' => 'Site', 'field' => 'site_name', 'kind' => 'categorical', 'group' => 'Meta'],
            ['id' => '_season', 'label' => 'Season', 'field' => 'season_label', 'kind' => 'categorical', 'group' => 'Meta'],
            ['id' => '_program', 'label' => 'Program', 'field' => 'program_name', 'kind' => 'categorical', 'group' => 'Meta'],
            ['id' => '_collection_date', 'label' => 'Collection date', 'field' => 'collection_date', 'kind' => 'date', 'group' => 'Meta'],
            ['id' => '_latitude', 'label' => 'Latitude', 'field' => 'latitude', 'kind' => 'number', 'group' => 'Geo'],
            ['id' => '_longitude', 'label' => 'Longitude', 'field' => 'longitude', 'kind' => 'number', 'group' => 'Geo'],
        ];

        foreach (WorkbookColumnRegistry::all() as $col) {
            $kind = $this->columnKind($col);
            $columns[] = [
                'id' => $col['column'],
                'label' => $col['column'].' · '.$col['field'],
                'field' => $col['field'],
                'kind' => $kind,
                'group' => $col['group'] ?? 'Workbook',
                'derived' => (bool) $col['derived'],
            ];
        }

        $records = $this->baseQuery($tool)->with(['site.program', 'season'])->get();

        return [
            'columns' => $columns,
            'filters' => [
                'sites' => $records->pluck('site')->filter()->unique('id')->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
                'seasons' => $records->pluck('season')->filter()->unique('id')->map(fn ($s) => ['id' => $s->id, 'label' => $s->label])->values()->all(),
                'programs' => $records->pluck('site.program')->filter()->unique('id')->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
            ],
        ];
    }

    /**
     * @param  array{site_ids?: list<int>, season_ids?: list<int>, program_id?: int|null}  $filters
     * @return array{rows: list<array<string, mixed>>, row_count: int}
     */
    public function rows(DcProject $tool, array $filters = []): array
    {
        $query = $this->baseQuery($tool)
            ->with(['workbookValues', 'indicator', 'site.program', 'season']);

        if (! empty($filters['site_ids'])) {
            $query->whereIn('site_id', $filters['site_ids']);
        }
        if (! empty($filters['season_ids'])) {
            $query->whereIn('season_id', $filters['season_ids']);
        }
        if (! empty($filters['program_id'])) {
            $query->whereHas('site', fn ($q) => $q->where('program_id', (int) $filters['program_id']));
        }

        $letters = WorkbookColumnRegistry::all();
        $out = [];

        foreach ($query->orderBy('id')->get() as $record) {
            if (! $this->consent->canExport($record)) {
                continue;
            }

            $map = $this->workbook->rawWorkbookMap($record);
            $row = [
                '_record_id' => $record->id,
                '_indicator_uid' => $record->indicator?->indicator_uid,
                '_site' => $record->site?->name,
                '_season' => $record->season?->label,
                '_program' => $record->site?->program?->name,
                '_collection_date' => $record->collection_date?->format('Y-m-d'),
                '_latitude' => $this->parseLatitude($map['Z'] ?? null),
                '_longitude' => $this->parseLongitude($map['Z'] ?? null),
            ];

            foreach ($letters as $col) {
                $letter = $col['column'];
                if ($col['derived']) {
                    $cell = $record->workbookValues->firstWhere('column_letter', $letter);
                    $row[$letter] = $cell?->value_number ?? $cell?->value_text;
                } else {
                    $row[$letter] = $map[$letter] ?? null;
                }
                $row[$letter] = $this->coerceCell($row[$letter], $col);
            }

            $out[] = $row;
        }

        return ['rows' => $out, 'row_count' => count($out)];
    }

    /** @param  array<string, mixed>  $col */
    private function columnKind(array $col): string
    {
        if ($col['derived']) {
            return 'number_or_text';
        }

        $input = WorkbookInputSpec::kind($col);

        return match ($input) {
            'date' => 'date',
            'yes_no', 'tercile', 'rating', 'rating_321', 'history_scale', 'lead_time_class', 'outcome' => 'number',
            'gps', 'photos' => 'text',
            default => 'text',
        };
    }

    /** @param  array<string, mixed>  $col */
    private function coerceCell(mixed $value, array $col): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $kind = $this->columnKind($col);
        if ($kind === 'number' && is_numeric($value)) {
            return str_contains((string) $value, '.') ? (float) $value : (int) $value;
        }
        if ($kind === 'date' && is_string($value)) {
            return substr($value, 0, 10);
        }

        return is_string($value) ? trim($value) : $value;
    }

    private function parseLatitude(mixed $gps): ?float
    {
        $pair = $this->parseGps($gps);

        return $pair[0] ?? null;
    }

    private function parseLongitude(mixed $gps): ?float
    {
        $pair = $this->parseGps($gps);

        return $pair[1] ?? null;
    }

    /** @return array{0: float, 1: float}|null */
    private function parseGps(mixed $gps): ?array
    {
        if (! is_string($gps) || ! str_contains($gps, ',')) {
            return null;
        }
        [$lat, $lng] = array_map('trim', explode(',', $gps, 2));
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }

    /** @return \Illuminate\Database\Eloquent\Builder<TilkitIndicatorRecord> */
    private function baseQuery(DcProject $tool): \Illuminate\Database\Eloquent\Builder
    {
        return TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id));
    }
}
