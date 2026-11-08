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
        $hierarchy = app(IndicatorHierarchy::class);
        foreach ($codes as $row) {
            $parsed = $hierarchy->parseLegendCodeSegments((string) $row->code);
            if ($parsed !== null) {
                $cat = $parsed['category'];
                $type = $parsed['type'];
            } else {
                $cat = str_pad((string) $row->category, 2, '0', STR_PAD_LEFT);
                $type = strtoupper((string) ($row->type ?? ''));
            }
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
                    $subKey = $sub !== '' ? $sub : '-';
                    $bySub[$subKey][] = $codeRow;
                }
                ksort($bySub);

                $subOut = [];
                foreach ($bySub as $subKey => $rows) {
                    $subOut[] = [
                        'key' => $subKey,
                        'label' => $subKey === '-' ? 'No sub-type code' : $subKey,
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
     * Horizontal mind-map payload for Setup: root → single legend-hierarchy pillar → L1–L3 drill-down.
     *
     * @return array{
     *     root: string,
     *     has_codes: bool,
     *     pillars: list<array{id: string, label: string, expandable: bool, blurb?: string}>,
     *     legend_levels: list<array{label: string, detail: string}>,
     *     categories: list<array{key: string, label: string, code_count: int, types: list<mixed>}>,
     *     pillar_trees: array<string, list<mixed>>,
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
        $hierarchy = app(IndicatorHierarchy::class);
        $legendTree = app(IndicatorLegendTree::class)->build();

        $categories = [];
        $codeTypes = [];

        foreach ($legendTree as $cat) {
            $typesOut = [];
            foreach ($cat['types'] as $type) {
                $subTypes = $this->mindmapSubTypesFromLegendRows($type['codes'] ?? []);
                $typeEntry = [
                    'key' => $type['key'],
                    'title' => $type['key'].' · '.$type['label'],
                    'code_count' => count($type['codes'] ?? []),
                    'sub_types' => $subTypes,
                ];
                $typesOut[] = $typeEntry;
                $codeTypes[] = $typeEntry;
            }

            $categories[] = [
                'key' => $cat['key'],
                'label' => $cat['label'],
                'code_count' => collect($typesOut)->sum('code_count'),
                'types' => $typesOut,
            ];
        }

        $codeCount = collect($categories)->sum('code_count');

        $legendLevels = collect($hierarchy->analyticalLevels())
            ->take(7)
            ->map(fn (array $level) => [
                'label' => sprintf('L%d %s · column %s', $level['level'], $level['name'], $level['workbook']),
                'detail' => (string) ($level['contains'] ?? ''),
            ])
            ->values()
            ->all();

        return [
            'root' => 'TILKIT v4 hierarchy → workbook A–D',
            'has_codes' => $codeCount > 0,
            'legend_levels' => $legendLevels,
            'categories' => $categories,
            'pillar_trees' => [],
            'pillars' => [
                [
                    'id' => 'code_categories',
                    'label' => 'Legend hierarchy (L1–L3)',
                    'expandable' => true,
                ],
            ],
            'code_types' => $codeTypes,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $codeRows  Rows from {@see IndicatorLegendTree::build()}.
     * @return list<array{key: string, label: string, code_count: int, codes: list<array<string, mixed>>}>
     */
    private function mindmapSubTypesFromLegendRows(array $codeRows): array
    {
        /** @var array<string, list<array<string, mixed>>> $bySub */
        $bySub = [];
        foreach ($codeRows as $row) {
            $sub = strtoupper(trim((string) ($row['sub_type'] ?? '')));
            if ($sub === '' && isset($row['code'])) {
                $parts = explode('-', strtoupper((string) $row['code']), 3);
                $sub = $parts[2] ?? '';
            }
            $subKey = $sub !== '' ? $sub : '-';
            $bySub[$subKey][] = $row;
        }
        ksort($bySub);

        $out = [];
        foreach ($bySub as $subKey => $rows) {
            $classifications = collect($rows)
                ->map(fn (array $r) => (string) ($r['classification_label'] ?? $r['label'] ?? ''))
                ->filter()
                ->unique()
                ->values();
            $out[] = [
                'key' => $subKey,
                'label' => $subKey === '-'
                    ? 'Sub-type not set'
                    : ($classifications->first()
                        ? $subKey.' - '.$classifications->first()
                        : $subKey),
                'code_count' => count($rows),
                'codes' => collect($rows)->map(function (array $row) {
                    $behaviours = is_array($row['behaviours'] ?? null) ? $row['behaviours'] : [];
                    $classification = (string) ($row['classification_label'] ?? $row['label'] ?? '');
                    $wb = is_array($row['workbook'] ?? null) ? $row['workbook'] : [];

                    return [
                        'code' => (string) ($row['code'] ?? ''),
                        'label' => $classification !== '' ? $classification : null,
                        'classification' => $classification,
                        'workbook_b' => (string) ($wb['B'] ?? ''),
                        'behaviour_count' => count($behaviours),
                        'sample_behaviour' => $behaviours[0] ?? null,
                    ];
                })->values()->all(),
            ];
        }

        return $out;
    }

    /** @param  list<TilkitIndicatorCode>  $typeRows */
    private function mindmapSubTypes(string $typeLabel, array $typeRows): array
    {
        /** @var array<string, list<TilkitIndicatorCode>> $bySub */
        $bySub = [];
        foreach ($typeRows as $row) {
            $sub = strtoupper(trim((string) ($row->sub_type ?? '')));
            $subKey = $sub !== '' ? $sub : '-';
            $bySub[$subKey][] = $row;
        }
        ksort($bySub);

        $out = [];
        foreach ($bySub as $subKey => $rows) {
            $labels = collect($rows)->pluck('label')->filter()->unique()->values();
            $out[] = [
                'key' => $subKey,
                'label' => $subKey === '-'
                    ? 'Sub-type not set'
                    : ($labels->first() ? $subKey.' - '.$labels->first() : $subKey),
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
