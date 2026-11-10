<?php

namespace App\Console\Commands;

use App\Models\DcProject;
use App\Services\KenyaDpa\TilkitPersonalDataService;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Console\Command;

class EnforceKenyaDpaRetentionCommand extends Command
{
    protected $signature = 'tilkit:enforce-retention {--dry-run : Count records without anonymizing}';

    protected $description = 'Anonymize TILKIT indicator rows past the tool retention period (Kenya DPA s.25(g)).';

    public function handle(TilkitPersonalDataService $personalData): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $projects = WeatherTool::singleToolMode() && WeatherTool::resolve()
            ? collect([WeatherTool::resolve()])
            : DcProject::query()->where('module', 'tilkit_weather')->get();

        $total = 0;
        foreach ($projects as $tool) {
            if ($tool === null) {
                continue;
            }
            $count = $personalData->enforceRetention($tool, $dryRun);
            $total += $count;
            $this->line(sprintf('%s: %d record(s)%s', $tool->code, $count, $dryRun ? ' (dry run)' : ' anonymized'));
        }

        $this->info("Total: {$total}");

        return self::SUCCESS;
    }
}
