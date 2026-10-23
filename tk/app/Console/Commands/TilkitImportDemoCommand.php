<?php

namespace App\Console\Commands;

use App\Models\DcProject;
use App\Services\Tilkit\WorkbookDemoImporter;
use Illuminate\Console\Command;

class TilkitImportDemoCommand extends Command
{
    protected $signature = 'tilkit:import-demo {tool : Tool ID} {--fixture= : Path to demo_workbook_full.json}';

    protected $description = 'Import v4 DEMO hypothetical workbook rows into a tool (all 12-sheet demo data for Indicator Log)';

    public function handle(WorkbookDemoImporter $importer): int
    {
        $tool = DcProject::query()->find($this->argument('tool'));
        if ($tool === null) {
            $this->error('Tool not found.');

            return self::FAILURE;
        }

        $fixture = $this->option('fixture')
            ?? base_path('tests/Fixtures/Tilkit/demo_workbook_full.json');

        if (! is_readable($fixture)) {
            $this->error("Fixture not readable: {$fixture}");

            return self::FAILURE;
        }

        $stats = $importer->importFixture($tool, $fixture);
        $this->info("Imported {$stats['records']} records across {$stats['sites']} sites and {$stats['seasons']} seasons.");

        return self::SUCCESS;
    }
}
