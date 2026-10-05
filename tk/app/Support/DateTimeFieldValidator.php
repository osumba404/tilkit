<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DateTimeFieldValidator
{
    /** Wire / storage format for {@see date} fields (ISO 8601 calendar date). */
    public const DATE_FORMAT = 'Y-m-d';

    /** Wire / storage format for {@see time} fields (24-hour clock with seconds). */
    public const TIME_FORMAT = 'H:i:s';

    /** Wire / storage format for {@see datetime} fields (local date-time, no timezone). */
    public const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * Validate and return a normalized string in the canonical format for the field type.
     */
    public static function parseAndValidate(string $fieldType, mixed $raw, string $fieldName = 'value'): string
    {
        if (! is_string($raw) || trim($raw) === '') {
            throw ValidationException::withMessages([
                $fieldName => self::requiredMessage($fieldType),
            ]);
        }

        $value = trim($raw);

        try {
            return match ($fieldType) {
                'date' => self::normalizeDate($value),
                'time' => self::normalizeTime($value),
                'datetime' => self::normalizeDatetime($value),
                default => $value,
            };
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                $fieldName => $e->getMessage(),
            ]);
        }
    }

    /** Value suitable for HTML `type="date|time|datetime-local"` after validation. */
    public static function forHtmlInput(string $fieldType, string $canonical): string
    {
        return match ($fieldType) {
            'date' => $canonical,
            'time' => strlen($canonical) >= 8 ? substr($canonical, 0, 8) : $canonical,
            'datetime' => str_contains($canonical, 'T')
                ? substr($canonical, 0, 19)
                : $canonical,
            default => $canonical,
        };
    }

    public static function formatHint(string $fieldType): string
    {
        return match ($fieldType) {
            'date' => 'YYYY-MM-DD',
            'time' => 'HH:MM:SS',
            'datetime' => 'YYYY-MM-DDTHH:MM:SS',
            default => '',
        };
    }

    private static function normalizeDate(string $value): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            throw new \InvalidArgumentException(
                'Date must use format '.self::formatHint('date').' (example: 2026-10-05).'
            );
        }

        $date = Carbon::createFromFormat('!'.self::DATE_FORMAT, $value);
        if ($date === false || $date->format(self::DATE_FORMAT) !== $value) {
            throw new \InvalidArgumentException('Date is not a valid calendar day.');
        }

        return $value;
    }

    private static function normalizeTime(string $value): string
    {
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return self::parseTimeWithFormat($value, 'H:i:s');
        }
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return self::parseTimeWithFormat($value, 'H:i');
        }

        throw new \InvalidArgumentException(
            'Time must use format '.self::formatHint('time').' (example: 14:30:00).'
        );
    }

    private static function parseTimeWithFormat(string $value, string $format): string
    {
        $time = Carbon::createFromFormat('!'.$format, $value);
        if ($time === false || ! self::matchesTimeInput($value, $format, $time)) {
            throw new \InvalidArgumentException(
                'Time must use format '.self::formatHint('time').' (example: 14:30:00).'
            );
        }

        return $time->format(self::TIME_FORMAT);
    }

    private static function normalizeDatetime(string $value): string
    {
        $normalized = str_replace(' ', 'T', $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $normalized) === 1) {
            return self::parseDatetimeWithFormat($normalized, 'Y-m-d\TH:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $normalized) === 1) {
            return self::parseDatetimeWithFormat($normalized, 'Y-m-d\TH:i');
        }

        throw new \InvalidArgumentException(
            'Datetime must use format '.self::formatHint('datetime').' (example: 2026-10-05T14:30:00).'
        );
    }

    private static function parseDatetimeWithFormat(string $value, string $format): string
    {
        $dt = Carbon::createFromFormat('!'.$format, $value);
        if ($dt === false || ! self::matchesDatetimeInput($value, $format, $dt)) {
            throw new \InvalidArgumentException(
                'Datetime must use format '.self::formatHint('datetime').' (example: 2026-10-05T14:30:00).'
            );
        }

        return $dt->format(self::DATETIME_FORMAT);
    }

    private static function matchesTimeInput(string $input, string $format, Carbon $parsed): bool
    {
        if ($format === 'H:i') {
            return preg_match('/^\d{2}:\d{2}$/', $input) === 1
                && $parsed->format('H:i') === $input;
        }

        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $input) === 1
            && $parsed->format('H:i:s') === $input;
    }

    private static function matchesDatetimeInput(string $input, string $format, Carbon $parsed): bool
    {
        if ($format === 'Y-m-d\TH:i') {
            return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $input) === 1
                && $parsed->format('Y-m-d\TH:i') === $input;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $input) === 1
            && $parsed->format('Y-m-d\TH:i:s') === $input;
    }

    private static function requiredMessage(string $fieldType): string
    {
        return match ($fieldType) {
            'date' => 'A valid date ('.self::formatHint('date').') is required.',
            'time' => 'A valid time ('.self::formatHint('time').') is required.',
            'datetime' => 'A valid date and time ('.self::formatHint('datetime').') is required.',
            default => 'This field is required.',
        };
    }
}
