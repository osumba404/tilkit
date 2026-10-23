<?php

namespace App\Console\Commands;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Services\Tilkit\WorkbookXlsxExporter;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Console\Command;

class TilkitExportWorkbookCommand extends Command
{
    protected $signature = 'tilkit:export-workbook {tool : Tool ID} {output : Output .xlsx path}';

    protected $description = 'Export consent-filtered indicator log rows to v4 column order xlsx';

    public function handle(WorkbookXlsxExporter $exporter): int
    {
        $tool = DcProject::query()->find($this->argument('tool')) ?? WeatherTool::resolve();
        if ($tool === null) {
            $this->error('Tool not found.');

            return self::FAILURE;
        }

        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['workbookValues', 'indicator'])
            ->get();

        try {
            $exporter->writeToPath($records, $this->argument('output'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Exported to '.$this->argument('output'));

        return self::SUCCESS;
    }
}
