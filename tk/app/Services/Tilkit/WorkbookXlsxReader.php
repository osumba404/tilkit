<?php

namespace App\Services\Tilkit;

class WorkbookXlsxReader
{
    /** @return list<array<string, mixed>> Rows keyed by column letter */
    public function readIndicatorLogRows(string $path): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException("Cannot read: {$path}");
        }

        $cfg = json_encode(config('tilkit.weather.indicator_log_xlsx'), JSON_THROW_ON_ERROR);
        $script = <<<'PY'
import json, sys, re
import openpyxl
path, cfg_json = sys.argv[1], sys.argv[2]
cfg = json.loads(cfg_json)
wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
ws = wb[cfg["sheet_name"]]
header_row = cfg["column_letter_row"]
min_row = cfg["min_scan_row"]
uid_col = cfg["uid_column"]
letters = {}
for row in ws.iter_rows(min_row=header_row, max_row=header_row, values_only=True):
    for idx, val in enumerate(row):
        if val and re.match(r"^[A-Z]{1,2}$", str(val).strip()):
            letters[idx] = str(val).strip()
uid_idx = next((i for i, L in letters.items() if L == uid_col), None)
uid_re = re.compile(r"^\d{2}-[A-Z]{3}-[A-Z]{3}-\d{3}$")
out = []
for ridx, row in enumerate(ws.iter_rows(min_row=min_row, values_only=True), min_row):
    if uid_idx is None or uid_idx >= len(row):
        continue
    ah = row[uid_idx]
    if not ah or not uid_re.match(str(ah).strip()):
        continue
    payload = {"_excel_row": ridx}
    for idx, letter in letters.items():
        if idx < len(row) and row[idx] is not None and str(row[idx]).strip() != "":
            payload[letter] = row[idx]
    out.append(payload)
wb.close()
print(json.dumps(out))
PY;
        $tmp = tempnam(sys_get_temp_dir(), 'tilkit_xlsx_').'.py';
        file_put_contents($tmp, $script);
        $output = shell_exec('python '.escapeshellarg($tmp).' '.escapeshellarg($path).' '.escapeshellarg($cfg).' 2>&1');
        @unlink($tmp);
        if (! $output) {
            throw new \RuntimeException('openpyxl read failed. Install: pip install openpyxl');
        }
        $rows = json_decode(trim($output), true);
        if (! is_array($rows)) {
            throw new \RuntimeException('Unexpected xlsx reader output: '.$output);
        }

        return $rows;
    }
}
