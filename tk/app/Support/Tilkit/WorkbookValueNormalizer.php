<?php

namespace App\Support\Tilkit;

class WorkbookValueNormalizer
{
    /** @return array{value_text: ?string, value_number: ?float, value_date: ?string, value_json: ?array} */
    public static function normalize(mixed $value, string $inputKind): array
    {
        $empty = [
            'value_text' => null,
            'value_number' => null,
            'value_date' => null,
            'value_json' => null,
        ];

        if ($value === null || $value === '') {
            return $empty;
        }

        return match ($inputKind) {
            'yes_no' => [
                ...$empty,
                'value_number' => self::yesNo($value),
            ],
            'rating', 'tercile' => [
                ...$empty,
                'value_number' => self::rating($value),
            ],
            'number', 'integer' => [
                ...$empty,
                'value_number' => self::number($value),
            ],
            'gps' => [
                ...$empty,
                'value_text' => is_scalar($value) ? trim((string) $value) : null,
            ],
            'date' => [
                ...$empty,
                'value_date' => self::date($value),
            ],
            'json' => [
                ...$empty,
                'value_json' => is_array($value) ? $value : json_decode((string) $value, true),
            ],
            'list_text' => self::normalizeListText($value),
            default => [
                ...$empty,
                'value_text' => is_scalar($value) ? (string) $value : json_encode($value),
            ],
        };
    }

    public static function display(?float $number, ?string $text, ?string $date, ?array $json, string $inputKind): mixed
    {
        return match ($inputKind) {
            'yes_no' => $number === null ? null : (int) $number,
            'rating', 'tercile' => $number === null ? null : (int) $number,
            'date' => $date,
            'json' => $json,
            'list_text' => self::displayListText($json, $text),
            default => $text,
        };
    }

    /** @return array{value_text: ?string, value_number: ?float, value_date: ?string, value_json: ?array} */
    private static function normalizeListText(mixed $value): array
    {
        $empty = [
            'value_text' => null,
            'value_number' => null,
            'value_date' => null,
            'value_json' => null,
        ];

        $items = WorkbookCapturePreparer::splitListText($value);
        $items = array_values(array_filter($items, fn ($v) => $v !== ''));

        if ($items === []) {
            return $empty;
        }

        return [
            ...$empty,
            'value_json' => $items,
            'value_text' => implode('; ', $items),
        ];
    }

    /** @return list<string> */
    private static function displayListText(?array $json, ?string $text): array
    {
        if (is_array($json) && $json !== []) {
            return array_values(array_map(fn ($v) => (string) $v, $json));
        }

        if ($text === null || trim($text) === '') {
            return [];
        }

        return WorkbookCapturePreparer::splitListText($text);
    }

    private static function yesNo(mixed $value): ?float
    {
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }
        $v = strtolower(trim((string) $value));
        if (in_array($v, ['1', 'yes', 'y', 'true'], true)) {
            return 1.0;
        }
        if (in_array($v, ['0', 'no', 'n', 'false'], true)) {
            return 0.0;
        }

        return null;
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function rating(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }
        $n = (int) $value;

        return ($n >= 0 && $n <= 3) ? (float) $n : null;
    }

    private static function date(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }
        $ts = strtotime($str);

        return $ts ? date('Y-m-d', $ts) : null;
    }
}
