<?php

namespace App\Support\Tilkit;

use App\Models\Tilkit\TilkitIndicatorCode;
use Illuminate\Support\Facades\Schema;

/** Data for the /dictionary reference browser (v4 legend + hierarchy). */
class IndicatorDictionaryPresenter
{
    public function __construct(
        private IndicatorHierarchy $hierarchy,
        private IndicatorBehaviourCatalog $behaviourCatalog,
    ) {}

    /** @return array{
     *     version: string,
     *     code_count: int,
     *     taxonomy: array{categories: array<string, string>, types: array<string, string>, category_order: list<string>},
     *     intensity_options: list<array{value: string, label: string}>,
     *     analytical_levels: list<array<string, mixed>>,
     *     community_stages: array<string, array<string, mixed>>,
     *     categories: list<array{key: string, label: string, types: list<array{key: string, label: string, codes: list<array<string, mixed>>}>}>,
     *     flat_codes: list<array<string, mixed>>,
     * }
     */
    public function build(): array
    {
        $taxonomy = $this->hierarchy->taxonomy();
        $catalogPath = config_path('tilkit_indicator_behaviour_catalog.json');
        $catalog = is_readable($catalogPath)
            ? json_decode((string) file_get_contents($catalogPath), true)
            : [];
        $byCode = is_array($catalog['by_code'] ?? null) ? $catalog['by_code'] : [];

        $dbLabels = [];
        if (Schema::hasTable('tilkit_indicator_codes')) {
            $dbLabels = TilkitIndicatorCode::query()
                ->whereNull('deprecated_at')
                ->pluck('label', 'code')
                ->all();
        }

        $bucket = [];
        $flat = [];

        foreach ($byCode as $legendCode => $entry) {
            $parts = explode('-', strtoupper((string) $legendCode), 3);
            if (count($parts) < 3) {
                continue;
            }
            [$cat, $typ, $sub] = $parts;
            $typeLine = "{$cat}-{$typ}";
            $classification = (string) ($dbLabels[$legendCode] ?? $entry['classification_label'] ?? '');

            $model = new TilkitIndicatorCode([
                'code' => $legendCode,
                'category' => $cat,
                'type' => $typ,
                'sub_type' => $sub,
                'label' => $classification,
            ]);
            $wb = $this->hierarchy->workbookDescriptor($model);

            $row = [
                'code' => $legendCode,
                'category' => $cat,
                'category_label' => $taxonomy['categories'][$cat] ?? $cat,
                'type_line' => $typeLine,
                'type_label' => $taxonomy['types'][$typeLine] ?? $typ,
                'sub_type' => $sub,
                'classification' => $classification,
                'workbook_a' => $wb['A'],
                'workbook_b' => $wb['B'],
                'behaviours' => $entry['behaviours'] ?? $this->behaviourCatalog->behavioursForLegendCode($legendCode),
            ];

            $flat[] = $row;
            $bucket[$cat][$typeLine]['key'] = $typeLine;
            $bucket[$cat][$typeLine]['label'] = $taxonomy['types'][$typeLine] ?? $typ;
            $bucket[$cat][$typeLine]['codes'][] = $row;
        }

        $categoryOrder = $taxonomy['category_order'] ?? array_keys($taxonomy['categories'] ?? []);
        $categories = [];
        foreach ($categoryOrder as $catKey) {
            if (! isset($bucket[$catKey])) {
                continue;
            }
            $types = array_values($bucket[$catKey]);
            usort($types, fn ($a, $b) => strcmp($a['key'], $b['key']));
            foreach ($types as &$type) {
                usort($type['codes'], fn ($a, $b) => strcmp($a['code'], $b['code']));
            }
            unset($type);

            $categories[] = [
                'key' => $catKey,
                'label' => $taxonomy['categories'][$catKey] ?? "Category {$catKey}",
                'types' => $types,
            ];
        }

        usort($flat, fn ($a, $b) => strcmp($a['code'], $b['code']));

        return [
            'version' => is_string($catalog['version'] ?? null) ? $catalog['version'] : 'v4',
            'code_count' => count($flat),
            'taxonomy' => $taxonomy,
            'intensity_options' => $this->behaviourCatalog->intensityOptions(),
            'analytical_levels' => $this->hierarchy->analyticalLevels(),
            'community_stages' => $this->hierarchy->communityDataListStages(),
            'categories' => $categories,
            'flat_codes' => $flat,
        ];
    }
}
