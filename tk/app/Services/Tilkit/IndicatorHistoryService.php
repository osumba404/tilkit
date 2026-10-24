<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\SeasonOutcome;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Support\Tilkit\IndicatorHistory;

class IndicatorHistoryService
{
    public function __construct(
        private EligibilityGate $gate,
    ) {}

    public function forIndicator(int $indicatorId, ?int $throughRecordId = null): IndicatorHistory
    {
        $query = TilkitIndicatorRecord::query()
            ->where('indicator_id', $indicatorId)
            ->when($throughRecordId, fn ($q) => $q->where('id', '<=', $throughRecordId));

        $records = $query->get();
        $deduped = $records->filter(fn (TilkitIndicatorRecord $r) => $this->gate->isFirstRecord($r));

        $corroboration = $deduped->filter(fn (TilkitIndicatorRecord $r) => $r->confirmed && $r->approved)->count();

        $scored = $deduped->filter(fn (TilkitIndicatorRecord $r) => $r->season_outcome !== null);

        $hits = $scored->filter(fn (TilkitIndicatorRecord $r) => $r->season_outcome === SeasonOutcome::Hit)->count();
        $misses = $scored->filter(fn (TilkitIndicatorRecord $r) => $r->season_outcome === SeasonOutcome::Miss)->count();
        $falseStarts = $scored->filter(fn (TilkitIndicatorRecord $r) => $r->season_outcome === SeasonOutcome::FalseStart)->count();

        return new IndicatorHistory(
            corroboration: $corroboration,
            scoredSeasons: $hits + $misses + $falseStarts,
            hits: $hits,
            misses: $misses,
            falseStarts: $falseStarts,
        );
    }
}
