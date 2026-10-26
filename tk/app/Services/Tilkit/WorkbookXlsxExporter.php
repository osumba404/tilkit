<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Support\Tilkit\WorkbookColumnRegistry;

class WorkbookXlsxExporter
{
    public function __construct(
        private WorkbookRecordService $workbook,
        private TilkitConsentGate $consent,
    ) {}

    /** @param  iterable<TilkitIndicatorRecord>  $records */
    public function writeToPath(iterable $records, string $outputPath): void
    {
        $columns = WorkbookColumnRegistry::all();
        $rows = [];
        foreach ($records as $record) {
            if (! $this->consent->canExport($record)) {
                continue;
            }
            $map = $this->workbook->rawWorkbookMap($record);
            $line = [];
            foreach ($columns as $col) {
                $letter = $col['column'];
                if ($col['derived']) {
                    $cell = $record->workbookValues->firstWhere('column_letter', $letter);
                    $line[] = $cell?->value_number ?? $cell?->value_text ?? '';
                } else {
                    $val = $map[$letter] ?? '';
                    if (is_array($val)) {
                        $val = implode('; ', $val);
                    }
                    $line[] = $val;
                }
            }
            $rows[] = $line;
        }

        $payload = json_encode([
            'columns' => array_map(fn ($c) => $c['column'], $columns),
            'rows' => $rows,
        ], JSON_THROW_ON_ERROR);

        $script = <<<'PY'
import json, sys
import openpyxl
out_path, payload_json = sys.argv[1], sys.argv[2]
data = json.loads(payload_json)
wb = openpyxl.Workbook()
ws = wb.active
ws.title = "Sheet 1 - Indicator Log"
for ci, letter in enumerate(data["columns"], 1):
    ws.cell(row=2, column=ci, value=letter)
for ri, row in enumerate(data["rows"], 12):
    for ci, val in enumerate(row, 1):
        ws.cell(row=ri, column=ci, value=val)
wb.save(out_path)
PY;
        $tmp = tempnam(sys_get_temp_dir(), 'tilkit_export_').'.py';
        file_put_contents($tmp, $script);
        $jsonTmp = tempnam(sys_get_temp_dir(), 'tilkit_payload_').'.json';
        file_put_contents($jsonTmp, $payload);
        shell_exec('python '.escapeshellarg($tmp).' '.escapeshellarg($outputPath).' '.escapeshellarg($jsonTmp).' 2>&1');
        @unlink($tmp);
        @unlink($jsonTmp);
        if (! is_file($outputPath)) {
            throw new \RuntimeException('xlsx export failed (is openpyxl installed?)');
        }
    }
}
