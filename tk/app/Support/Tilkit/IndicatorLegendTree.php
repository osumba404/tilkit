<?php

namespace App\Support\Tilkit;

use App\Models\Tilkit\TilkitIndicatorCode;

/** JSON-friendly legend tree for cascading pickers (category → type → code). */
class IndicatorLegendTree
{
    /**
     * @param  list<int>|null  $allowedCodeIds  When set, only these indicator_code ids are included.
     * @return list<array{key: string, label: string, types: list<array{key: string, label: string, codes: list<array{id: int, code: string, sub_type: string|null, label: string|null}>}>}>
     */
    public function build(?array $allowedCodeIds = null): array
    {
        $taxonomy = $this->taxonomy();
        $categoryOrder = $taxonomy['category_order'] ?? array_keys($taxonomy['categories'] ?? []);
        $allowedMap = $allowedCodeIds !== null ? array_flip(array_map('intval', $allowedCodeIds)) : null;

        $codes = TilkitIndicatorCode::query()
            ->whereNull('deprecated_at')
            ->orderBy('category')
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        if ($allowedMap !== null) {
            $codes = $codes->filter(fn (TilkitIndicatorCode $c) => isset($allowedMap[(int) $c->id]));
        }

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
                    'codes' => collect($typeCodes)->map(function (TilkitIndicatorCode $c) {
                        $catalog = app(IndicatorBehaviourCatalog::class);

                        $hierarchy = app(IndicatorHierarchy::class);
                        $wb = $hierarchy->workbookDescriptor($c);

                        return [
                            'id' => (int) $c->id,
                            'code' => $c->code,
                            'sub_type' => $c->sub_type,
                            'label' => $c->label,
                            'type_line' => $hierarchy->typeLineForCode($c),
                            'classification_label' => $catalog->classificationLabel((string) $c->code) ?? $c->label,
                            'behaviours' => $catalog->behavioursForCode($c),
                            'workbook' => $wb,
                        ];
                    })->values()->all(),
                ];
            }
            usort($typesOut, fn ($a, $b) => strcmp($a['key'], $b['key']));

            $out[] = [
                'key' => $catKey,
                'label' => $taxonomy['categories'][$catKey] ?? 'Category '.$catKey,
                'types' => $typesOut,
            ];
        }

        return $out;
    }

    /** @return array{categories: array<string, string>, types: array<string, string>, category_order: list<string>} */
    public function taxonomy(): array
    {
        $path = config_path('tilkit_indicator_taxonomy.json');
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];

        return is_array($data) ? $data : ['categories' => [], 'types' => [], 'category_order' => []];
    }

    /** @return array{A: string, B: string, C: string, D: string} */
    public function workbookDescriptor(TilkitIndicatorCode $code, ?string $specificName = null): array
    {
        return app(IndicatorHierarchy::class)->workbookDescriptor($code, $specificName);
    }
}
