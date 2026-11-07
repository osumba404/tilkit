<?php

namespace App\Support\Tilkit;

use App\Models\Tilkit\TilkitIndicatorCode;
use InvalidArgumentException;

/**
 * Canonical TILKIT indicator hierarchy and workbook field ownership (v4).
 *
 * Analytical stack: category (01–06) → type line (7) → sub-type → legend code → unique ID → capture columns.
 */
class IndicatorHierarchy
{
    /** @var array<string, list<string>> */
    private const TYPES_BY_CATEGORY = [
        '01' => ['PLT', 'ANI'],
        '02' => ['ATM'],
        '03' => ['AST'],
        '04' => ['HYD'],
        '05' => ['RIT'],
        '06' => ['SEN'],
    ];

    private const CANONICAL_TYPE_LINES = [
        '01-PLT',
        '01-ANI',
        '02-ATM',
        '03-AST',
        '04-HYD',
        '05-RIT',
        '06-SEN',
    ];

    /** @return array{categories: array<string, string>, types: array<string, string>, category_order: list<string>} */
    public function taxonomy(): array
    {
        $path = config_path('tilkit_indicator_taxonomy.json');
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];

        return is_array($data) ? $data : ['categories' => [], 'types' => [], 'category_order' => []];
    }

    /**
     * @return array{category: string, type: string, sub_type: string}|null
     */
    public function parseLegendCodeSegments(string $legendCode): ?array
    {
        $parts = explode('-', strtoupper(trim($legendCode)), 3);
        if (count($parts) < 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            return null;
        }

        return [
            'category' => str_pad($parts[0], 2, '0', STR_PAD_LEFT),
            'type' => $parts[1],
            'sub_type' => $parts[2],
        ];
    }

    public function typeLineForCode(TilkitIndicatorCode $code): string
    {
        $parsed = $this->parseLegendCodeSegments((string) $code->code);
        if ($parsed !== null) {
            return $parsed['category'].'-'.$parsed['type'];
        }

        $cat = str_pad((string) $code->category, 2, '0', STR_PAD_LEFT);
        $typ = strtoupper(trim((string) ($code->type ?? '')));

        return $cat.'-'.$typ;
    }

    public function compareCanonicalTypeLines(string $typeLineA, string $typeLineB): int
    {
        $order = array_flip(self::CANONICAL_TYPE_LINES);
        $rankA = $order[$typeLineA] ?? 999;
        $rankB = $order[$typeLineB] ?? 999;
        if ($rankA !== $rankB) {
            return $rankA <=> $rankB;
        }

        return strcmp($typeLineA, $typeLineB);
    }

    public function assertLegendRowValid(string $legendCode, string $category, string $type, string $subType): void
    {
        $category = str_pad($category, 2, '0', STR_PAD_LEFT);
        $type = strtoupper(trim($type));
        $subType = strtoupper(trim($subType));
        $legendCode = strtoupper(trim($legendCode));

        $expected = "{$category}-{$type}-{$subType}";
        if ($legendCode !== $expected) {
            throw new InvalidArgumentException("Legend code {$legendCode} does not match {$expected}.");
        }

        $typeLine = "{$category}-{$type}";
        if (! in_array($typeLine, self::CANONICAL_TYPE_LINES, true)) {
            throw new InvalidArgumentException("Type line {$typeLine} is not one of the seven v4 lines.");
        }

        $allowed = self::TYPES_BY_CATEGORY[$category] ?? [];
        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException("Type {$type} is not valid for category {$category}.");
        }
    }

    /**
     * Workbook columns owned at registration / capture (Community Data List §A — describing the sign).
     *
     * @return array{A: string, B: string, C: string, D: string}
     */
    public function workbookDescriptor(TilkitIndicatorCode $code, ?string $registeredName = null): array
    {
        $tax = $this->taxonomy();
        $parsed = $this->parseLegendCodeSegments((string) $code->code);
        $cat = $parsed['category'] ?? str_pad((string) $code->category, 2, '0', STR_PAD_LEFT);
        $typeLine = $this->typeLineForCode($code);
        $typeLabel = $tax['types'][$typeLine] ?? strtoupper((string) ($code->type ?? ''));
        $sub = $parsed['sub_type'] ?? strtoupper(trim((string) ($code->sub_type ?? '')));
        $classification = trim((string) ($code->label ?? ''));
        $catLabel = $tax['categories'][$cat] ?? "Category {$cat}";

        $columnB = $typeLine;
        if ($sub !== '') {
            $columnB .= ' · '.$sub;
        }
        if ($classification !== '') {
            $columnB .= ' — '.$classification;
        }

        return [
            'A' => "{$cat} — {$catLabel}",
            'B' => $columnB,
            'C' => trim((string) ($registeredName ?? '')),
            'D' => '',
        ];
    }

    /**
     * Whether free text in a workbook column is structurally wrong for this legend code (cross-level bleed).
     */
    public function columnBleedViolation(TilkitIndicatorCode $code, string $column, ?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $column = strtoupper($column);
        $typeLine = $this->typeLineForCode($code);
        $lower = strtolower($value);

        return match ($column) {
            'A' => $this->bleedIfMatches($lower, [
                'pattern' => '/\b(01|02|03|04|05|06)-(plt|ani|atm|ast|hyd|rit|sen)\b/',
                'message' => 'Column A must be the broad category (01–06), not a type line or legend code.',
            ]),
            'B' => $this->domainBleed($typeLine, $lower, 'Column B must stay classification (type line + sub-type), not another domain’s phenomenon.'),
            'C' => null,
            'D' => $this->domainBleed($typeLine, $lower, 'Column D behaviour must match the selected type line domain.'),
            'E' => null,
            'U' => preg_match('/\b(blossom|bird|flying|frog)\b/', $lower)
                ? 'Column U is rainfall tercile (3/2/1), not a phenomenon description.'
                : null,
            default => null,
        };
    }

    /** @param  array{pattern: string, message: string}  $rule */
    private function bleedIfMatches(string $lower, array $rule): ?string
    {
        return preg_match($rule['pattern'], $lower) ? $rule['message'] : null;
    }

    private function domainBleed(string $typeLine, string $lower, string $message): ?string
    {
        $forbidden = $this->forbiddenTokensForTypeLine($typeLine);
        foreach ($forbidden as $token) {
            if (str_contains($lower, $token)) {
                return $message.' (conflicts with '.$typeLine.')';
            }
        }

        return null;
    }

    /** @return list<string> */
    private function forbiddenTokensForTypeLine(string $typeLine): array
    {
        $path = config_path('tilkit_indicator_behaviour_catalog.json');
        if (! is_readable($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        $map = is_array($data) ? ($data['forbidden_cross_domain'] ?? []) : [];

        return is_array($map[$typeLine] ?? null) ? $map[$typeLine] : [];
    }

    /**
     * Metadata for docs, validators, and UI hints (all hierarchy stages).
     *
     * @return list<array<string, mixed>>
     */
    public function analyticalLevels(): array
    {
        return [
            [
                'level' => 1,
                'name' => 'Category',
                'code_shape' => '01–06',
                'workbook' => 'A',
                'contains' => 'Broad domain: biological, atmospheric, celestial, hydrological, ritual, sensory.',
                'excludes' => 'Type lines, sub-types, behaviours, tercile forecasts, GPS, dates.',
            ],
            [
                'level' => 2,
                'name' => 'Type line',
                'code_shape' => '01-PLT … 06-SEN (7 lines)',
                'workbook' => 'B (part)',
                'contains' => 'One of seven Community Data List families.',
                'excludes' => 'Other domains (e.g. bird migration under 01-PLT).',
            ],
            [
                'level' => 3,
                'name' => 'Sub-type',
                'code_shape' => '3-letter segment (e.g. FLW)',
                'workbook' => 'B (part)',
                'contains' => 'Phenomenological class within the type line.',
                'excludes' => 'Full local sign name, seasonal observation narrative.',
            ],
            [
                'level' => 4,
                'name' => 'Legend code',
                'code_shape' => 'CC-TTT-SSS',
                'workbook' => 'B + AH prefix',
                'contains' => 'Controlled vocabulary row on Indicator Code Legend.',
                'excludes' => 'Site, season, hit/miss scoring.',
            ],
            [
                'level' => 5,
                'name' => 'Unique Indicator ID',
                'code_shape' => 'CC-TTT-SSS-###',
                'workbook' => 'AH',
                'contains' => 'Stable ID for one registered sign.',
                'excludes' => 'Per-sighting detail (use N, O, CI).',
            ],
            [
                'level' => 6,
                'name' => 'Registered sign',
                'code_shape' => 'n/a',
                'workbook' => 'C',
                'contains' => 'Local / English / scientific name for this sign.',
                'excludes' => 'Generic legend gloss, other signs’ names.',
            ],
            [
                'level' => 7,
                'name' => 'Observable behaviour (season)',
                'code_shape' => 'n/a',
                'workbook' => 'D',
                'contains' => 'What was seen/heard this season for this sign (domain-specific options).',
                'excludes' => 'Classification label, unrelated domains, tercile amount (use U).',
            ],
            [
                'level' => 8,
                'name' => 'Local prediction text',
                'code_shape' => 'n/a',
                'workbook' => 'E',
                'contains' => 'What the sign means for weather in local words.',
                'excludes' => 'Indicator code strings, workshop ratings.',
            ],
            [
                'level' => 9,
                'name' => 'Prediction facets',
                'code_shape' => 'n/a',
                'workbook' => 'H, P–U',
                'contains' => 'Onset, cessation, distribution, amount (tercile) coded fields.',
                'excludes' => 'Phenomenon description that belongs in D.',
            ],
        ];
    }

    /** Community Data List capture stages (A–E) with primary hierarchy touchpoints. */
    public function communityDataListStages(): array
    {
        return [
            'A' => [
                'title' => 'For every sign',
                'columns' => ['A', 'B', 'C', 'D', 'E', 'H', 'J–M', 'P–U', 'V', 'AC', 'AD', 'AP'],
                'hierarchy_note' => 'Establish classification (A–B), name the sign (C), describe behaviour (D), lock predictions (H, P–U).',
            ],
            'B' => [
                'title' => 'With every sighting',
                'columns' => ['N', 'O', 'Y–AB', 'AR', 'CI', 'CK', 'CL'],
                'hierarchy_note' => 'Evidence and dating—must reference an existing AH ID; do not reclassify the sign.',
            ],
            'C' => [
                'title' => 'After the season',
                'columns' => ['AS–AV', 'AZ'],
                'hierarchy_note' => 'Actual weather and hit/miss; no change to A–B classification.',
            ],
            'D' => [
                'title' => 'Data desk',
                'columns' => ['AH', 'AI', 'AM', 'AE', 'AF', 'BL–CH'],
                'hierarchy_note' => 'IDs, weights, derived fields—classification must match legend.',
            ],
        ];
    }
}
