<?php

namespace App\Console\Commands;

use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Services\Tilkit\TilkitEngineService;
use Illuminate\Console\Command;

class TilkitTallyCommand extends Command
{
    protected $signature = 'tilkit:tally {site : Site ID or name} {season : Season ID or label} {--publish : Store immutable snapshot}';

    protected $description = 'Run v4 seasonal tercile tally for a site and season';

    public function handle(TilkitEngineService $engine): int
    {
        $site = $this->resolveSite($this->argument('site'));
        $season = $this->resolveSeason($this->argument('season'));

        if ($site === null || $season === null) {
            $this->error('Site or season not found.');

            return self::FAILURE;
        }

        $result = $engine->runTally($site->id, $season->id, (bool) $this->option('publish'));

        $this->info("Counted rows: {$result->countedRows} · Reconciliation: {$result->reconciliation}");
        foreach ([3 => 'Above normal', 2 => 'Normal', 1 => 'Below normal'] as $k => $label) {
            $s = $result->simple[$k]['percent'] ?? 0;
            $w = $result->weighted[$k]['percent'] ?? 0;
            $p = $result->priorityAdjusted[$k]['percent'] ?? 0;
            $this->line("{$label}: simple {$s}% · weighted {$w}% · priority {$p}%");
        }

        if ($result->isTieWeighted) {
            $this->warn('Weighted tally: Tie');
        }

        return self::SUCCESS;
    }

    private function resolveSite(string $key): ?TilkitSite
    {
        if (ctype_digit($key)) {
            return TilkitSite::query()->find($key);
        }

        return TilkitSite::query()->where('name', $key)->first();
    }

    private function resolveSeason(string $key): ?TilkitSeason
    {
        if (ctype_digit($key)) {
            return TilkitSeason::query()->find($key);
        }

        return TilkitSeason::query()->where('label', $key)->first();
    }
}
