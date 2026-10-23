<?php

namespace App\Console\Commands;

use App\Models\DcProject;
use App\Services\Tilkit\WorkbookDemoImporter;
use App\Services\Tilkit\WorkbookXlsxReader;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Console\Command;

class TilkitImportWorkbookCommand extends Command
{
    protected $signature = 'tilkit:import-workbook {tool : Tool ID or code} {path? : Path to v4 xlsx}';

    protected $description = 'Import Indicator Log rows from a v4 workbook xlsx (Sheet 1, from row 12)';

    public function handle(WorkbookXlsxReader $reader, WorkbookDemoImporter $importer): int
    {
        $toolArg = $this->argument('tool');
        $tool = DcProject::query()->where('id', $toolArg)->orWhere('code', $toolArg)->first();
        if ($tool === null && WeatherTool::singleToolMode()) {
            $tool = WeatherTool::resolve();
        }
        if ($tool === null) {
            $this->error('Tool not found.');

            return self::FAILURE;
        }

        $path = $this->argument('path')
            ?? config('tilkit.weather.docs_path').'/TILKIT_Weather_Indicator_Log_v4_DEMO_hypothetical_data.xlsx';

        try {
            $rows = $reader->readIndicatorLogRows($path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->warn('No indicator rows found (check sheet name and AH column).');

            return self::FAILURE;
        }

        $stats = $importer->importRows($tool, $rows);
        $this->info("Imported {$stats['records']} rows from {$path} ({$stats['sites']} sites, {$stats['seasons']} seasons).");

        return self::SUCCESS;
    }
}
