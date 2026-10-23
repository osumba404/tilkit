<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\Tilkit\TilkitTallySnapshot;
use App\Support\Tilkit\TallyResult;
use Illuminate\Support\Facades\DB;

class TilkitEngineService
{
    public function __construct(
        private TilkitRecordEngine $recordEngine,
        private TallyEngine $tallyEngine,
    ) {}

    public function recalculateRecord(TilkitIndicatorRecord $record): void
    {
        $this->recordEngine->recalculate($record);
    }

    public function runTally(int $siteId, int $seasonId, bool $publish = false, ?int $userId = null): TallyResult
    {
        $settings = TilkitSeasonSettings::query()
            ->where('site_id', $siteId)
            ->where('season_id', $seasonId)
            ->first();

        $result = $this->tallyEngine->run($siteId, $seasonId, $settings);

        if ($publish) {
            $this->publishSnapshot($result, $settings, $userId);
        }

        return $result;
    }

    public function recalculateSiteSeason(int $siteId, int $seasonId): void
    {
        $records = TilkitIndicatorRecord::query()
            ->where('site_id', $siteId)
            ->where('season_id', $seasonId)
            ->get();

        foreach ($records as $record) {
            $this->recordEngine->recalculate($record);
        }
    }

    private function publishSnapshot(TallyResult $result, ?TilkitSeasonSettings $settings, ?int $userId): void
    {
        DB::transaction(function () use ($result, $settings, $userId): void {
            TilkitTallySnapshot::query()->create([
                'site_id' => $result->siteId,
                'season_id' => $result->seasonId,
                'protocol_version' => config('tilkit.weather.protocol_version'),
                'use_prior_multiplier' => $settings?->use_prior_multiplier ?? false,
                'settings_payload' => $settings?->settings,
                'results' => $result->toArray(),
                'reconciliation' => $result->reconciliation,
                'published_at' => now(),
                'created_by' => $userId,
            ]);
        });
    }
}
