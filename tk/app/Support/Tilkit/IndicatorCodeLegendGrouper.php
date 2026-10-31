<?php

namespace App\Support\Tilkit;

use App\Models\Tilkit\TilkitIndicatorCode;
class IndicatorCodeLegendGrouper
{
    /** @return list<array{key: string, label: string, types: list<array{key: string, label: string, codes: list<array{code: string, label: string|null, sub_type: string|null}>}>}> */
    public function groupedLegend(): array
    {
        $taxonomy = $this->taxonomy();
        $categoryOrder = $taxonomy['category_order'] ?? array_keys($taxonomy['categories'] ?? []);

        $codes = TilkitIndicatorCode::query()
            ->whereNull('deprecated_at')
            ->orderBy('category')
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        /** @var array<string, array<string, list<TilkitIndicatorCode>>> $bucket */
        $bucket = [];
        foreach ($codes as $row) {
            $cat = str_pad((string) $row->category, 2, '0', STR_PAD_LEFT);
            $type = strtoupper((string) ($row->type ?? ''));
            $bucket[$cat][$type][] = $row;
        }

        $out = [];
        foreach ($categoryOrder as $catKey) {
            if (! isset($bucket[$catKey])) {
                continue;
            }
            $typesOut = [];
            foreach ($bucket[$catKey] as $typeKey => $typeCodes) {
                $typeLine = $catKey.'-'.$typeKey;
                $typesOut[] = [
                    'key' => $typeLine,
                    'label' => $taxonomy['types'][$typeLine] ?? ($typeKey !== '' ? $typeKey : 'Other'),
                    'codes' => collect($typeCodes)->map(fn (TilkitIndicatorCode $c) => [
                        'code' => $c->code,
                        'label' => $c->label,
                        'sub_type' => $c->sub_type,
                    ])->values()->all(),
                ];
            }
            usort($typesOut, fn ($a, $b) => strcmp($a['key'], $b['key']));

            $out[] = [
                'key' => $catKey,
                'label' => ($taxonomy['categories'][$catKey] ?? 'Category '.$catKey),
                'types' => $typesOut,
            ];
        }

        return $out;
    }

    /**
     * Top-down tree for setup mind map: category → type → sub-type → codes.
     *
     * @return array{
     *     capture_levels: list<array{workbook: string, title: string, detail: string}>,
     *     categories: list<array{
     *         key: string,
     *         label: string,
     *         code_count: int,
     *         types: list<array{
     *             key: string,
     *             label: string,
     *             code_count: int,
     *             sub_types: list<array{key: string, label: string, code_count: int, sample_codes: list<string>}>
     *         }>
     *     }>
     * }
     */
    public function taxonomyMindmap(): array
    {
        $groups = $this->groupedLegend();

        $categories = [];
        foreach ($groups as $cat) {
            $typesOut = [];
            foreach ($cat['types'] as $type) {
                /** @var array<string, list<array{code: string, label: string|null, sub_type: string|null}>> $bySub */
                $bySub = [];
                foreach ($type['codes'] as $codeRow) {
                    $sub = strtoupper(trim((string) ($codeRow['sub_type'] ?? '')));
                    $subKey = $sub !== '' ? $sub : '—';
                    $bySub[$subKey][] = $codeRow;
                }
                ksort($bySub);

                $subOut = [];
                foreach ($bySub as $subKey => $rows) {
                    $subOut[] = [
                        'key' => $subKey,
                        'label' => $subKey === '—' ? 'No sub-type code' : $subKey,
                        'code_count' => count($rows),
                        'sample_codes' => collect($rows)->take(4)->pluck('code')->values()->all(),
                    ];
                }

                $typesOut[] = [
                    'key' => $type['key'],
                    'label' => $type['label'],
                    'code_count' => count($type['codes']),
                    'sub_types' => $subOut,
                ];
            }

            $categories[] = [
                'key' => $cat['key'],
                'label' => $cat['label'],
                'code_count' => collect($typesOut)->sum('code_count'),
                'types' => $typesOut,
            ];
        }

        return [
            'capture_levels' => [
                [
                    'workbook' => 'A',
                    'title' => 'Indicator category',
                    'detail' => 'Broad group (01–06)',
                ],
                [
                    'workbook' => 'B',
                    'title' => 'Type & sub-type',
                    'detail' => 'e.g. 01-ANI · BMG',
                ],
                [
                    'workbook' => 'C',
                    'title' => 'Registered sign',
                    'detail' => 'Local / English name at capture',
                ],
                [
                    'workbook' => 'D',
                    'title' => 'Behaviour',
                    'detail' => 'What the sign looks or sounds like',
                ],
            ],
            'categories' => $categories,
        ];
    }

    /**
     * Horizontal mind-map payload (root → workbook pillars → legend types → examples).
     *
     * @return array{
     *     root: string,
     *     has_codes: bool,
     *     pillars: list<array{id: string, label: string, expandable: bool, blurb?: string}>,
     *     legend_levels: list<array{label: string, detail: string}>,
     *     code_types: list<array{
     *         key: string,
     *         title: string,
     *         code_count: int,
     *         sub_types: list<array{
     *             key: string,
     *             label: string,
     *             code_count: int,
     *             codes: list<array{code: string, label: string|null}>
     *         }>
     *     }>
     * }
     */
    public function analysisMindmap(): array
    {
        $taxonomy = $this->taxonomy();
        /** @var list<string> $canonicalTypeLines */
        $canonicalTypeLines = array_keys($taxonomy['types'] ?? []);

        $codes = TilkitIndicatorCode::query()
            ->whereNull('deprecated_at')
            ->orderBy('code')
            ->get();

        /** @var array<string, list<TilkitIndicatorCode>> $byCanonicalType */
        $byCanonicalType = array_fill_keys($canonicalTypeLines, []);
        $nonStandard = [];

        foreach ($codes as $row) {
            $typeLine = $this->typeLineFromCode((string) $row->code);
            if ($typeLine !== null && isset($byCanonicalType[$typeLine])) {
                $byCanonicalType[$typeLine][] = $row;
            } else {
                $nonStandard[] = $row;
            }
        }

        $codeTypes = [];
        foreach ($canonicalTypeLines as $typeKey) {
            $typeRows = $byCanonicalType[$typeKey];
            if ($typeRows === []) {
                continue;
            }
            $typeLabel = $taxonomy['types'][$typeKey] ?? $typeKey;
            $codeTypes[] = [
                'key' => $typeKey,
                'title' => $this->typeMindmapTitleForKey($typeKey, $typeLabel, $taxonomy),
                'code_count' => count($typeRows),
                'sub_types' => $this->mindmapSubTypes($typeLabel, $typeRows),
            ];
        }

        if ($nonStandard !== []) {
            $codeTypes[] = [
                'key' => '_non_standard',
                'title' => 'Non-standard legend rows ('.count($nonStandard).')',
                'code_count' => count($nonStandard),
                'sub_types' => [[
                    'key' => 'review',
                    'label' => 'Not one of the 7 v4 type lines — check master xlsx',
                    'code_count' => count($nonStandard),
                    'codes' => collect($nonStandard)->map(fn (TilkitIndicatorCode $c) => [
                        'code' => $c->code,
                        'label' => $c->label,
                    ])->values()->all(),
                ]],
            ];
        }

        return [
            'root' => 'TILKIT Indicator Analysis from Top to Behavior',
            'has_codes' => $codes->isNotEmpty(),
            'legend_levels' => [
                [
                    'label' => 'Category · workbook A',
                    'detail' => '6 groups (01–06) — biological, atmospheric, astronomical, hydrological, ritual, sensory',
                ],
                [
                    'label' => 'Type line · workbook B',
                    'detail' => '7 lines per Community Data List: 01-PLT, 01-ANI, 02-ATM, 03-AST, 04-HYD, 05-RIT, 06-SEN',
                ],
                [
                    'label' => 'Sub-type · legend + B',
                    'detail' => 'Third segment of the code (e.g. FRO in 01-ANI-FRO)',
                ],
                [
                    'label' => 'Legend code',
                    'detail' => 'Full XX-YYY-ZZZ row on Indicator Code Legend sheet',
                ],
                [
                    'label' => 'Registered sign · C, D, AH',
                    'detail' => 'Specific names (C), behaviour (D), unique indicator ID (AH) at capture',
                ],
            ],
            'pillars' => [
                [
                    'id' => 'code_categories',
                    'label' => 'Indicator Code Categories',
                    'expandable' => true,
                ],
                [
                    'id' => 'uid',
                    'label' => 'Unique Indicator ID Structure',
                    'expandable' => false,
                    'blurb' => 'Workbook column AH — each registered sign gets a unique ID linked to the master legend (e.g. 01-ANI-FRO-001).',
                ],
                [
                    'id' => 'log_fields',
                    'label' => 'Indicator Log Description Fields',
                    'expandable' => false,
                    'blurb' => 'Columns A–C — category, type & sub-type, then local / English / scientific names for the specific sign.',
                ],
                [
                    'id' => 'history',
                    'label' => 'Indicator History and Reliability',
                    'expandable' => false,
                    'blurb' => 'Columns J–M — how long the community has trusted the sign, lead time, and historical vs current-season reliability.',
                ],
                [
                    'id' => 'sighting',
                    'label' => 'Field Sighting and Evidence',
                    'expandable' => false,
                    'blurb' => 'Columns N, O, Z, Y, CI — sighted this season, place description, GPS, photos, and field sighting dates.',
                ],
                [
                    'id' => 'behavior',
                    'label' => 'Indicator Behavior and Manifestation',
                    'expandable' => false,
                    'blurb' => 'Column D — what the sign looks, sounds, or feels like when it warns about weather (behaviour at capture).',
                ],
            ],
            'code_types' => $codeTypes,
        ];
    }

    /** @param  list<TilkitIndicatorCode>  $typeRows */
    private function mindmapSubTypes(string $typeLabel, array $typeRows): array
    {
        /** @var array<string, list<TilkitIndicatorCode>> $bySub */
        $bySub = [];
        foreach ($typeRows as $row) {
            $sub = strtoupper(trim((string) ($row->sub_type ?? '')));
            $subKey = $sub !== '' ? $sub : '—';
            $bySub[$subKey][] = $row;
        }
        ksort($bySub);

        $out = [];
        foreach ($bySub as $subKey => $rows) {
            $labels = collect($rows)->pluck('label')->filter()->unique()->values();
            $out[] = [
                'key' => $subKey,
                'label' => $subKey === '—'
                    ? 'Sub-type not set'
                    : ($labels->first() ? $subKey.' — '.$labels->first() : $subKey),
                'code_count' => count($rows),
                'codes' => collect($rows)->map(fn (TilkitIndicatorCode $c) => [
                    'code' => $c->code,
                    'label' => $c->label,
                ])->values()->all(),
            ];
        }

        return $out;
    }

    private function typeLineFromCode(string $code): ?string
    {
        $parts = explode('-', strtoupper(trim($code)), 3);
        if (count($parts) < 2) {
            return null;
        }

        return $parts[0].'-'.$parts[1];
    }

    /** @param  array{categories: array<string, string>, types: array<string, string>}  $taxonomy */
    private function typeMindmapTitleForKey(string $typeKey, string $typeLabel, array $taxonomy): string
    {
        $catKey = substr($typeKey, 0, 2);
        $catLabel = $taxonomy['categories'][$catKey] ?? '';
        $family = trim(explode('/', $catLabel)[0]);

        if ($catKey === '01') {
            $kind = str_contains($typeKey, 'PLT') ? 'Plant-based' : 'Animal-based';

            return "Biological: {$kind} ({$typeKey})";
        }

        if ($catKey === '05') {
            return "Ritual and Divinatory ({$typeKey})";
        }

        if ($catKey === '06') {
            return "Sensory / body ({$typeKey})";
        }

        return ($family !== '' ? rtrim($family).': ' : '')."{$typeLabel} ({$typeKey})";
    }

    /** @return array{categories: array<string, string>, types: array<string, string>, category_order: list<string>} */
    private function taxonomy(): array
    {
        $path = config_path('tilkit_indicator_taxonomy.json');
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];

        return is_array($data) ? $data : ['categories' => [], 'types' => [], 'category_order' => []];
    }
}
