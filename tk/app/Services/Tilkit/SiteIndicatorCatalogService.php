<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitSiteMonthIndicatorCode;
use App\Support\Tilkit\SeasonMonthCoverage;
use Illuminate\Support\Collection;

class SiteIndicatorCatalogService
{
    /** @return array<int, list<int>> month 1–12 => indicator_code_id list */
    public function assignmentsByMonth(TilkitSite $site): array
    {
        $rows = TilkitSiteMonthIndicatorCode::query()
            ->where('site_id', $site->id)
            ->orderBy('calendar_month')
            ->orderBy('indicator_code_id')
            ->get();

        $out = array_fill(1, 12, []);
        foreach ($rows as $row) {
            $m = (int) $row->calendar_month;
            if ($m >= 1 && $m <= 12) {
                $out[$m][] = (int) $row->indicator_code_id;
            }
        }

        return $out;
    }

    /** @param  array<int, list<int>>  $byMonth */
    public function syncAssignments(TilkitSite $site, array $byMonth): void
    {
        TilkitSiteMonthIndicatorCode::query()->where('site_id', $site->id)->delete();

        $programId = (int) $site->program_id;
        $projectId = (int) $site->project_id;

        for ($month = 1; $month <= 12; $month++) {
            $ids = array_values(array_unique(array_map('intval', $byMonth[$month] ?? [])));
            foreach ($ids as $codeId) {
                if ($codeId <= 0) {
                    continue;
                }
                TilkitSiteMonthIndicatorCode::query()->create([
                    'project_id' => $projectId,
                    'program_id' => $programId,
                    'site_id' => $site->id,
                    'calendar_month' => $month,
                    'indicator_code_id' => $codeId,
                ]);
            }
        }
    }

    /** Codes assigned to this site for one calendar month. */
    public function codeIdsForSiteMonth(int $siteId, int $calendarMonth): Collection
    {
        if ($calendarMonth < 1 || $calendarMonth > 12) {
            return collect();
        }

        return TilkitSiteMonthIndicatorCode::query()
            ->where('site_id', $siteId)
            ->where('calendar_month', $calendarMonth)
            ->pluck('indicator_code_id')
            ->map(fn ($id) => (int) $id);
    }

    /** Union of codes across all months covered by a season (deduplicated). */
    public function codeIdsForSeasonUnion(TilkitSite $site, TilkitSeason $season): array
    {
        $months = SeasonMonthCoverage::monthsInSeason(
            (int) $season->start_month,
            (int) $season->end_month,
        );

        $ids = [];
        foreach ($months as $month) {
            foreach ($this->codeIdsForSiteMonth((int) $site->id, $month) as $id) {
                $ids[$id] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * Allowed legend codes for capture: site catalogue for collection month,
     * and that month must fall inside the selected season range.
     *
     * @return list<int> indicator_code_id
     */
    public function allowedCodeIdsForCapture(TilkitSite $site, TilkitSeason $season, int $collectionMonth): array
    {
        if ($collectionMonth < 1 || $collectionMonth > 12) {
            return [];
        }

        $seasonMonths = SeasonMonthCoverage::monthsInSeason(
            (int) $season->start_month,
            (int) $season->end_month,
        );

        if (! in_array($collectionMonth, $seasonMonths, true)) {
            return [];
        }

        return $this->codeIdsForSiteMonth((int) $site->id, $collectionMonth)->values()->all();
    }

    public function collectionMonthFromDate(string $date): int
    {
        $ts = strtotime($date);

        return $ts ? (int) date('n', $ts) : 0;
    }
}
