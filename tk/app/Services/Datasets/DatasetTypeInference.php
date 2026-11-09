<?php

namespace App\Services\Datasets;

class DatasetTypeInference
{
    /**
     * @return array{
     *     row_count: int,
     *     columns: list<array{name: string, inferred_type: string}>
     * }
     */
    public function profileCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['row_count' => 0, 'columns' => []];
        }

        /** @var list<string>|false $headers */
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return ['row_count' => 0, 'columns' => []];
        }

        $columnCount = count($headers);
        /** @var list<list<string>> $samples */
        $samples = array_fill(0, $columnCount, []);
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            foreach ($row as $index => $value) {
                if ($index >= $columnCount) {
                    break;
                }
                if (count($samples[$index]) < 20) {
                    $samples[$index][] = (string) $value;
                }
            }
        }

        fclose($handle);

        $columns = [];
        foreach ($headers as $index => $name) {
            $columns[] = [
                'name' => $name,
                'inferred_type' => $this->inferType($samples[$index] ?? []),
            ];
        }

        return ['row_count' => $rowCount, 'columns' => $columns];
    }

    /** @param  list<string>  $values */
    private function inferType(array $values): string
    {
        if ($values === []) {
            return 'text';
        }

        $allBool = true;
        $allInt = true;
        $allFloat = true;

        foreach ($values as $value) {
            $trim = trim($value);
            if ($trim === '') {
                continue;
            }

            $lower = strtolower($trim);
            if (! in_array($lower, ['true', 'false', '0', '1', 'yes', 'no'], true)) {
                $allBool = false;
            } elseif (! in_array($lower, ['true', 'false'], true)) {
                $allBool = false;
            }

            if (filter_var($trim, FILTER_VALIDATE_INT) === false) {
                $allInt = false;
            }

            if (! is_numeric($trim)) {
                $allFloat = false;
            }
        }

        if ($allBool && $values !== []) {
            return 'boolean';
        }
        if ($allInt) {
            return 'integer';
        }
        if ($allFloat) {
            return 'number';
        }

        return 'text';
    }
}
