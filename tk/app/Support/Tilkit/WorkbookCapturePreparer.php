<?php

namespace App\Support\Tilkit;

class WorkbookCapturePreparer
{
    /** @param  array<string, mixed>  $workbook */
    public static function normalizeIncoming(array $workbook): array
    {
        if (array_key_exists('Z_lat', $workbook) || array_key_exists('Z_lng', $workbook)) {
            $lat = trim((string) ($workbook['Z_lat'] ?? ''));
            $lng = trim((string) ($workbook['Z_lng'] ?? ''));
            if ($lat !== '' || $lng !== '') {
                $workbook['Z'] = $lat.','.$lng;
            }
            unset($workbook['Z_lat'], $workbook['Z_lng']);
        }

        if (array_key_exists('BC_level', $workbook)) {
            $level = trim((string) ($workbook['BC_level'] ?? ''));
            $share = trim((string) ($workbook['BC_share'] ?? ''));
            $credit = trim((string) ($workbook['BC_credit'] ?? ''));
            if ($level !== '' || $share !== '' || $credit !== '') {
                $workbook['BC'] = implode('; ', array_filter([$level, $share, $credit], fn ($v) => $v !== ''));
            }
            unset($workbook['BC_level'], $workbook['BC_share'], $workbook['BC_credit']);
        }

        return $workbook;
    }

    /**
     * @return list<string>
     */
    public static function splitListText(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [''];
        }

        if (is_array($value)) {
            $items = array_values(array_filter(
                array_map(fn ($v) => trim((string) $v), $value),
                fn ($v) => $v !== '',
            ));

            return $items === [] ? [''] : $items;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [''];
            }

            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return self::splitListText($decoded);
            }

            $parts = preg_split('/\r\n|\n|\r|;/', $trimmed) ?: [];
            $items = array_values(array_filter(array_map('trim', $parts), fn ($v) => $v !== ''));

            return $items === [] ? [''] : $items;
        }

        return [''];
    }

    /** @return array{lat: string, lng: string} */
    public static function splitGps(mixed $value): array
    {
        if (! is_scalar($value)) {
            return ['lat' => '', 'lng' => ''];
        }
        $parts = array_map('trim', explode(',', (string) $value, 2));

        return [
            'lat' => $parts[0] ?? '',
            'lng' => $parts[1] ?? '',
        ];
    }

    /** @return array{level: string, share: string, credit: string} */
    public static function splitConsent(mixed $value): array
    {
        if (! is_scalar($value)) {
            return ['level' => '', 'share' => '', 'credit' => ''];
        }
        $parts = array_map('trim', explode(';', (string) $value));

        return [
            'level' => $parts[0] ?? '',
            'share' => $parts[1] ?? '',
            'credit' => $parts[2] ?? '',
        ];
    }
}
