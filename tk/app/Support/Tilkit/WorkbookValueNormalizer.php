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
            'date' => [
                ...$empty,
                'value_date' => self::date($value),
            ],
            'json' => [
                ...$empty,
                'value_json' => is_array($value) ? $value : json_decode((string) $value, true),
            ],
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
            default => $text,
        };
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
