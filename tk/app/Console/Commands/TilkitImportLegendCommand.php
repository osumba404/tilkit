<?php

namespace App\Console\Commands;

use App\Models\Tilkit\TilkitIndicatorCode;
use Illuminate\Console\Command;

class TilkitImportLegendCommand extends Command
{
    protected $signature = 'tilkit:import-legend {path? : Path to master template xlsx}';

    protected $description = 'Import indicator code legend rows from the v4 master template xlsx';

    public function handle(): int
    {
        $path = $this->argument('path')
            ?? config('tilkit.weather.docs_path').'/TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx';

        if (! is_readable($path)) {
            $this->error("Cannot read: {$path}");

            return self::FAILURE;
        }

        if (! class_exists(\OpenSpout\Reader\XLSX\Reader::class) && ! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->importViaPython($path);
        }

        return $this->importViaOpenPyxlCli($path);
    }

    private function importViaPython(string $path): int
    {
        $script = <<<'PY'
import json, sys
import openpyxl
path = sys.argv[1]
wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
ws = wb["Indicator Code Legend"]
rows = []
for row in ws.iter_rows(min_row=6, values_only=True):
    if not row or not row[0]:
        continue
    cat, cat_name, typ, typ_name, sub, sub_name = (row + (None,) * 6)[:6]
    if not cat or not typ or not sub:
        continue
    cat_s, typ_s, sub_s = str(cat).strip(), str(typ).strip(), str(sub).strip()
    if not cat_s.isdigit() or len(cat_s) != 2:
        continue
    if len(typ_s) > 8 or len(sub_s) > 8:
        continue
    code = f"{cat_s}-{typ_s}-{sub_s}"
    rows.append({"code": code, "category": str(cat), "type": str(typ or ""), "sub_type": str(sub or ""), "label": str(sub_name or "")[:200]})
print(json.dumps(rows))
PY;
        $tmp = tempnam(sys_get_temp_dir(), 'tilkit_legend_').'.py';
        file_put_contents($tmp, $script);
        $output = shell_exec('python '.escapeshellarg($tmp).' '.escapeshellarg($path).' 2>&1');
        @unlink($tmp);
        if (! $output) {
            $this->error('Python/openpyxl import failed. Install: pip install openpyxl');

            return self::FAILURE;
        }

        $rows = json_decode(trim($output), true);
        if (! is_array($rows)) {
            $this->error('Unexpected import output: '.$output);

            return self::FAILURE;
        }

        return $this->persistRows($rows);
    }

    private function importViaOpenPyxlCli(string $path): int
    {
        return $this->importViaPython($path);
    }

    /** @param  list<array<string, string>>  $rows */
    private function persistRows(array $rows): int
    {
        $version = config('tilkit.weather.protocol_version');
        $count = 0;
        foreach ($rows as $row) {
            TilkitIndicatorCode::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'category' => $row['category'],
                    'type' => $row['type'] ?: null,
                    'sub_type' => $row['sub_type'] ?: null,
                    'label' => $row['label'] ?: null,
                    'protocol_version' => $version,
                ]
            );
            $count++;
        }
        $this->info("Imported {$count} indicator code rows.");

        return self::SUCCESS;
    }
}
