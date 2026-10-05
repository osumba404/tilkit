<?php

namespace App\Support;

class FieldTypeHelper
{
    /** Readable catalogue labels keyed by type code (shown beside monospace code). */
    private const TYPE_LABELS = [
        'text' => 'Text',
        'note' => 'Note',
        'integer' => 'Integer',
        'decimal' => 'Decimal',
        'range' => 'Range',
        'select_one' => 'Select One',
        'select_multiple' => 'Select Multiple',
        'rank' => 'Rank',
        'date' => 'Date',
        'time' => 'Time',
        'datetime' => 'Datetime',
        'geopoint' => 'Geopoint',
        'geotrace' => 'Geotrace',
        'geoshape' => 'Geoshape',
        'image' => 'Image',
        'audio' => 'Audio',
        'video' => 'Video',
        'file' => 'File',
        'barcode' => 'Barcode',
        'signature' => 'Signature',
        'calculate' => 'Calculate',
        'group' => 'Section',
        'repeat' => 'Repeat',
        'metadata' => 'Metadata',
        'acknowledge' => 'Acknowledge',
    ];

    /** @return array<string, string> SVG path / viewBox icons keyed by type code */
    public static function icons(): array
    {
        return [
            'text' => 'M4 6h16M4 12h10M4 18h14',
            'note' => 'M7 8h10M7 12h10M7 16h6',
            'integer' => 'M8 7v10M12 7v10M16 7v10',
            'decimal' => 'M4 12h16M8 8v8M16 6v12',
            'range' => 'M4 12h16M8 12a2 2 0 1 0 0-4M16 12a2 2 0 1 1 0 4',
            'select_one' => 'M8 9l4 4 6-6',
            'select_multiple' => 'M5 9l3 3 6-6M5 15l3 3 6-6',
            'rank' => 'M8 6v12M12 8l4 4-4 4M4 8l4 4-4 4',
            'date' => 'M6 4h12v16H6zM6 9h12',
            'time' => 'M12 8v5l3 2M12 22a10 10 0 1 0 0-20',
            'datetime' => 'M6 4h12v16H6zM12 11v4',
            'geopoint' => 'M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11zM12 11a2 2 0 1 0 0-4',
            'geotrace' => 'M4 17l4-8 4 5 4-9 4 12',
            'geoshape' => 'M4 8l6-4 8 3 4 9H4z',
            'image' => 'M4 6h16v12H4zM8 10a2 2 0 1 0 0-4M4 16l4-5 3 3 5-7 4 9',
            'audio' => 'M9 9v6a3 3 0 0 0 6 0V8M12 19v3',
            'video' => 'M4 7h10v10H4zM14 10l6-3v10l-6-3',
            'file' => 'M8 4h8l4 4v12H8zM12 4v4h4',
            'barcode' => 'M4 8h2v8H4M8 8h1v8H8M11 8h2v8h-2M15 8h1v8h-1M18 8h2v8h-2',
            'signature' => 'M4 18c3-4 6-4 9-1s6 3 9-1',
            'calculate' => 'M8 8h8v8H8zM10 10h4M10 14h4',
            'group' => 'M4 6h16M4 12h10M4 18h12',
            'repeat' => 'M4 7h16v10H4zM8 11h8',
            'metadata' => 'M4 6h16M4 10h16M4 14h10',
            'acknowledge' => 'M5 12l4 4 10-10',
        ];
    }

    public static function label(string $code): string
    {
        return self::TYPE_LABELS[$code] ?? ucwords(str_replace('_', ' ', $code));
    }

    public static function supportsChoices(string $code): bool
    {
        return in_array($code, ['select_one', 'select_multiple', 'rank'], true);
    }

    public static function supportsMediaCapture(string $code): bool
    {
        return in_array($code, ['image', 'audio', 'video', 'file', 'signature'], true);
    }

    /** @return array<int, array{code: string, label: string, icon: string}> */
    public static function catalog(): array
    {
        $icons = self::icons();

        return collect($icons)->map(fn ($path, $code) => [
            'code' => $code,
            'label' => self::label($code),
            'icon' => $path,
        ])->values()->all();
    }
}
