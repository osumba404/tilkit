<?php

namespace App\Services\Datasets;

class DatasetTypeInference
{
    /** @return array{row_count: int, columns: list<array<string, mixed>>} */
    public function profileCsv(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return ['row_count' => 0, 'columns' => []];
        }

        $headers = fgetcsv($handle);
        if ($headers === false || $headers === [null]) {
            fclose($handle);

            return ['row_count' => 0, 'columns' => []];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $samples = array_fill(0, count($headers), []);
        $nullCounts = array_fill(0, count($headers), 0);
        $rowCount = 0;
        $sampleLimit = 200;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            if ($rowCount > $sampleLimit) {
                continue;
            }
            foreach ($headers as $i => $_) {
                $val = $row[$i] ?? null;
                if ($val === null || trim((string) $val) === '') {
                    $nullCounts[$i]++;

                    continue;
                }
                if (count($samples[$i]) < 8) {
                    $samples[$i][] = trim((string) $val);
                }
            }
        }

        while (fgetcsv($handle) !== false) {
            $rowCount++;
        }

        fclose($handle);

        $columns = [];
        foreach ($headers as $i => $name) {
            if ($name === '') {
                $name = 'column_'.($i + 1);
            }
            $columns[] = [
                'name' => $name,
                'position' => $i,
                'inferred_type' => $this->inferType($samples[$i]),
                'sample_values' => $samples[$i],
                'null_count' => $nullCounts[$i],
            ];
        }

        return ['row_count' => $rowCount, 'columns' => $columns];
    }

    /** @param list<string> $samples */
    private function inferType(array $samples): string
    {
        if ($samples === []) {
            return 'text';
        }

        $bool = 0;
        $numeric = 0;
        $datetime = 0;
        $total = count($samples);

        foreach ($samples as $val) {
            $lower = strtolower($val);
            if (in_array($lower, ['true', 'false', 'yes', 'no', '0', '1'], true)) {
                $bool++;
            }
            if (is_numeric(str_replace(',', '', $val))) {
                $numeric++;
            }
            if (strtotime($val) !== false && preg_match('/\d{4}|\d{1,2}[\/\-]\d{1,2}/', $val)) {
                $datetime++;
            }
        }

        if ($bool === $total) {
            return 'boolean';
        }
        if ($numeric >= max(1, (int) ($total * 0.8))) {
            return str_contains(implode('', $samples), '.') ? 'decimal' : 'integer';
        }
        if ($datetime >= max(1, (int) ($total * 0.6))) {
            return 'datetime';
        }

        return 'text';
    }
}
