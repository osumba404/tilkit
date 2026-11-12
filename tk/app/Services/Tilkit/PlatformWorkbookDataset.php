<?php

namespace App\Services\Tilkit;

/**
 * Workbook rows for platform screenshots - Kenya and Ghana programmes (v4 workbook).
 */
class PlatformWorkbookDataset
{
    public function __construct(
        private KenyaGhanaPilotDataset $source = new KenyaGhanaPilotDataset,
    ) {}

    /** @return list<array<string, mixed>> */
    public function kenyaRows(): array
    {
        return $this->scrubRows($this->source->kenyaRows());
    }

    /** @return list<array<string, mixed>> */
    public function ghanaRows(): array
    {
        return $this->scrubRows($this->source->ghanaRows());
    }

    /** @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function scrubRows(array $rows): array
    {
        foreach ($rows as $i => $row) {
            foreach ($row as $letter => $value) {
                if (is_string($value)) {
                    $row[$letter] = $this->scrubString($value);
                }
            }
            $rows[$i] = $row;
        }

        return $rows;
    }

    private function scrubString(string $value): string
    {
        $value = str_replace(
            [
                'Mutirithia (DEMO)',
                '(DEMO)',
                '(demo)',
                'Six-sign demo',
                'Demo history row',
                'Pilot data desk',
                'Kenya-Ghana pilot seed',
                'Kenya NEO pilot',
                'Ghana pilot',
                'Pilot observer group',
                'Pilot observer',
                'pilot ·',
                'pilot ',
                ' pilot',
                'DEMO',
                'demo',
            ],
            [
                'Mutirithia',
                '',
                '',
                'Mutirithia',
                'Field capture archive note',
                'Data desk verification',
                'Kenya–Ghana programme workbook',
                'Kenya NEO programme',
                'Ghana programme',
                'Community observer circle',
                'Community observer',
                'programme ·',
                'programme ',
                ' programme',
                '',
                '',
            ],
            $value,
        );

        $value = preg_replace('/\b(seed|seeder|fixture|placeholder|mock|faker)\b/i', '', $value) ?? $value;
        $value = preg_replace('/\s{2,}/', ' ', $value) ?? $value;
        $value = trim($value, " ·\t");

        return $value;
    }
}
