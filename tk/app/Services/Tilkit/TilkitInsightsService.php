<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\TercileForecast;
use App\Enums\Tilkit\ValidationStatus;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitSighting;
use App\Models\Tilkit\TilkitTallySnapshot;
use App\Support\Tilkit\WorkbookCapturePreparer;
use Illuminate\Support\Collection;

class TilkitInsightsService
{
    public function __construct(private WorkbookRecordService $workbook) {}
    /** @return array<string, mixed> */
    public function summary(DcProject $tool): array
    {
        $base = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id));

        $total = (clone $base)->count();
        $sighted = (clone $base)->where('sighted', true)->count();
        $confirmed = (clone $base)->where('confirmed', true)->count();
        $approved = (clone $base)->where('approved', true)->count();
        $contested = (clone $base)->where('validation_status', ValidationStatus::Contested)->count();

        $bySite = TilkitSite::query()
            ->where('project_id', $tool->id)
            ->withCount('records')
            ->orderBy('name')
            ->get()
            ->map(fn (TilkitSite $site) => [
                'site' => $site->name,
                'count' => (int) $site->records_count,
            ]);

        $bySeason = TilkitSeason::query()
            ->where('project_id', $tool->id)
            ->withCount('records')
            ->orderBy('label')
            ->get()
            ->map(fn (TilkitSeason $season) => [
                'season' => $season->label,
                'count' => (int) $season->records_count,
            ]);

        $snapshots = TilkitTallySnapshot::query()
            ->whereHas('site', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['site', 'season'])
            ->latest('published_at')
            ->limit(5)
            ->get();

        $tercileBreakdown = $this->countGrouped($tool, 'tercile_forecast', fn ($value) => $value instanceof TercileForecast
            ? $value->label()
            : 'unknown');

        $validationBreakdown = $this->countGrouped($tool, 'validation_status', fn ($value) => $value instanceof ValidationStatus
            ? $value->excelLabel()
            : 'Not set');

        $sightings = TilkitSighting::query()
            ->whereHas('record.indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->count();

        $funnelMax = max(1, $total);

        return [
            'total_records' => $total,
            'sighted' => $sighted,
            'confirmed' => $confirmed,
            'approved' => $approved,
            'contested' => $contested,
            'field_sightings' => $sightings,
            'by_site' => $bySite,
            'by_season' => $bySeason,
            'recent_snapshots' => $snapshots,
            'tercile_breakdown' => $tercileBreakdown,
            'validation_breakdown' => $validationBreakdown,
            'collection_funnel' => [
                ['label' => 'Workbook rows', 'count' => $total, 'pct' => 100],
                ['label' => 'Sighted', 'count' => $sighted, 'pct' => (int) round($sighted / $funnelMax * 100)],
                ['label' => 'Confirmed', 'count' => $confirmed, 'pct' => (int) round($confirmed / $funnelMax * 100)],
                ['label' => 'Approved', 'count' => $approved, 'pct' => (int) round($approved / $funnelMax * 100)],
            ],
            'site_max' => max(1, $bySite->max('count') ?? 1),
            'season_max' => max(1, $bySeason->max('count') ?? 1),
        ];
    }

    /**
     * @param  callable(mixed): string  $label
     * @return list<array{label: string, count: int}>
     */
    private function countGrouped(DcProject $tool, string $column, callable $label): array
    {
        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->get([$column]);

        $counts = [];
        foreach ($records as $record) {
            $key = $label($record->{$column});
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        arsort($counts);

        return collect($counts)
            ->map(fn (int $count, string $key) => ['label' => $key, 'count' => $count])
            ->values()
            ->all();
    }

    /** @return Collection<int, TilkitIndicatorRecord> */
    public function recentRecords(DcProject $tool, int $limit = 8): Collection
    {
        return TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['indicator', 'site', 'season'])
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Map markers for dashboard views (sightings, workbook column Z, site anchors).
     *
     * @return list<array{lat: float, lng: float, label: string, detail: string, kind: string, href?: string}>
     */
    public function mapPointsForTool(DcProject $tool, ?int $siteId = null, ?int $seasonId = null, int $limit = 400): array
    {
        $points = [];

        TilkitSite::query()
            ->when($siteId, fn ($q) => $q->whereKey($siteId))
            ->where('project_id', $tool->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->each(function (TilkitSite $site) use (&$points): void {
                $points[] = $this->point(
                    (float) $site->latitude,
                    (float) $site->longitude,
                    $site->name,
                    'Monitoring site',
                    'site',
                );
            });

        TilkitSighting::query()
            ->whereHas('record.indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->when($siteId, fn ($q) => $q->whereHas('record', fn ($r) => $r->where('site_id', $siteId)))
            ->when($seasonId, fn ($q) => $q->whereHas('record', fn ($r) => $r->where('season_id', $seasonId)))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['record.indicator', 'record.site'])
            ->latest('sighted_at')
            ->limit($limit)
            ->each(function (TilkitSighting $sighting) use (&$points, $tool): void {
                $record = $sighting->record;
                $points[] = $this->point(
                    (float) $sighting->latitude,
                    (float) $sighting->longitude,
                    $record?->indicator?->indicator_uid ?? 'Field sighting',
                    trim(($record?->site?->name ?? '').' · '.($sighting->sighted_at?->format('Y-m-d') ?? 'Sighting')),
                    'sighting',
                    $record ? route('tools.tilkit.records.edit', [$tool, $record]) : null,
                );
            });

        TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->with(['indicator', 'site', 'workbookValues'])
            ->latest('updated_at')
            ->limit($limit)
            ->each(function (TilkitIndicatorRecord $record) use (&$points, $tool): void {
                $map = $this->workbook->rawWorkbookMap($record);
                $gps = WorkbookCapturePreparer::splitGps($map['Z'] ?? '');
                if ($gps['lat'] === '' || $gps['lng'] === '') {
                    return;
                }
                $lat = (float) $gps['lat'];
                $lng = (float) $gps['lng'];
                if (! $this->isValidCoord($lat, $lng)) {
                    return;
                }
                $points[] = $this->point(
                    $lat,
                    $lng,
                    $record->indicator?->indicator_uid ?? 'Workbook row',
                    trim(($record->site?->name ?? '').' · column Z'),
                    'workbook',
                    route('tools.tilkit.records.edit', [$tool, $record]),
                );
            });

        return $points;
    }

    /** @return array{lat: float, lng: float, label: string, detail: string, kind: string, href?: string} */
    private function point(float $lat, float $lng, string $label, string $detail, string $kind, ?string $href = null): array
    {
        $row = [
            'lat' => $lat,
            'lng' => $lng,
            'label' => $label,
            'detail' => $detail,
            'kind' => $kind,
        ];
        if ($href !== null) {
            $row['href'] = $href;
        }

        return $row;
    }

    private function isValidCoord(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }
}
