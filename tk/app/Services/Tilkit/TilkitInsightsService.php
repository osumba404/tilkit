<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\ValidationStatus;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitTallySnapshot;
use Illuminate\Support\Collection;

class TilkitInsightsService
{
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
            ->latest('published_at')
            ->limit(5)
            ->get();

        return [
            'total_records' => $total,
            'sighted' => $sighted,
            'confirmed' => $confirmed,
            'approved' => $approved,
            'contested' => $contested,
            'by_site' => $bySite,
            'by_season' => $bySeason,
            'recent_snapshots' => $snapshots,
        ];
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
}
