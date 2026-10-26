<?php

namespace App\Services\Tilkit;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitConsensusForecast;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitTallySnapshot;
use App\Models\Tilkit\TilkitWeightReview;
use App\Models\Tilkit\TilkitWorkbookMedia;
use App\Models\Tilkit\TilkitWorkbookValue;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Support\Facades\DB;

class TilkitWeatherDataReset
{
    /** Remove all TILKIT weather workbook data; keep user accounts and indicator code catalogue. */
    public function wipeWeatherToolData(): void
    {
        DB::transaction(function (): void {
            $tools = DcProject::query()
                ->where('module', 'tilkit_weather')
                ->orWhere('code', WeatherTool::configuredCode())
                ->get();

            foreach ($tools as $tool) {
                $this->wipeProject($tool);
            }
        });
    }

    private function wipeProject(DcProject $tool): void
    {
        $indicatorIds = TilkitIndicator::query()
            ->where('project_id', $tool->id)
            ->pluck('id');

        if ($indicatorIds->isNotEmpty()) {
            $recordIds = TilkitIndicatorRecord::query()
                ->whereIn('indicator_id', $indicatorIds)
                ->pluck('id');

            if ($recordIds->isNotEmpty()) {
                TilkitWeightReview::query()->whereIn('indicator_record_id', $recordIds)->delete();
                TilkitWorkbookMedia::query()->whereIn('indicator_record_id', $recordIds)->delete();
                TilkitWorkbookValue::query()->whereIn('indicator_record_id', $recordIds)->delete();
                TilkitIndicatorRecord::query()->whereIn('id', $recordIds)->delete();
            }

            TilkitIndicator::query()->whereIn('id', $indicatorIds)->delete();
        }

        $siteIds = TilkitSite::query()->where('project_id', $tool->id)->pluck('id');
        if ($siteIds->isNotEmpty()) {
            TilkitSeasonSettings::query()->whereIn('site_id', $siteIds)->delete();
            TilkitConsensusForecast::query()->whereIn('site_id', $siteIds)->delete();
            TilkitTallySnapshot::query()->whereIn('site_id', $siteIds)->delete();
        }

        TilkitSite::query()->where('project_id', $tool->id)->delete();
        TilkitSeason::query()->where('project_id', $tool->id)->delete();
        TilkitProgram::query()->where('project_id', $tool->id)->delete();
    }
}
