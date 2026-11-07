<?php

namespace App\Support\Tilkit;

use App\Models\Tilkit\TilkitIndicatorCode;

/** Domain-consistent observable behaviours (column D) per legend code. */
class IndicatorBehaviourCatalog
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /** @return array<int, array{value: string, label: string}> */
    public function intensityOptions(): array
    {
        $opts = $this->data()['intensity_options'] ?? [];

        return is_array($opts) ? $opts : [];
    }

    /**
     * @return list<string>
     */
    public function behavioursForCode(TilkitIndicatorCode $code): array
    {
        return $this->behavioursForLegendCode(strtoupper(trim((string) $code->code)));
    }

    /**
     * @return list<string>
     */
    public function behavioursForLegendCode(string $legendCode): array
    {
        $legendCode = strtoupper(trim($legendCode));
        $entry = $this->data()['by_code'][$legendCode] ?? null;
        if (! is_array($entry)) {
            return [];
        }
        $list = $entry['behaviours'] ?? [];

        return is_array($list) ? array_values(array_map('strval', $list)) : [];
    }

    public function classificationLabel(string $legendCode): ?string
    {
        $legendCode = strtoupper(trim($legendCode));
        $entry = $this->data()['by_code'][$legendCode] ?? null;
        if (! is_array($entry)) {
            return null;
        }
        $label = $entry['classification_label'] ?? null;

        return is_string($label) && $label !== '' ? $label : null;
    }

    public function composeColumnD(string $behaviour, ?string $intensity = null): string
    {
        $behaviour = trim($behaviour);
        if ($behaviour === '') {
            return '';
        }
        $intensity = $intensity !== null ? trim($intensity) : '';
        if ($intensity === '') {
            return $behaviour;
        }

        return $behaviour.' | Intensity: '.$intensity;
    }

    /**
     * @return array{behaviour: string, intensity: string|null}
     */
    public function parseColumnD(?string $columnD): array
    {
        $columnD = trim((string) $columnD);
        if ($columnD === '') {
            return ['behaviour' => '', 'intensity' => null];
        }
        if (preg_match('/^(.+?)\s*\|\s*Intensity:\s*(.+)$/iu', $columnD, $m)) {
            return ['behaviour' => trim($m[1]), 'intensity' => trim($m[2])];
        }

        return ['behaviour' => $columnD, 'intensity' => null];
    }

    public function isAllowedBehaviour(TilkitIndicatorCode $code, string $behaviour): bool
    {
        $behaviour = trim($behaviour);
        if ($behaviour === '') {
            return false;
        }

        return in_array($behaviour, $this->behavioursForCode($code), true);
    }

    public function isAllowedIntensity(?string $intensity): bool
    {
        if ($intensity === null || trim($intensity) === '') {
            return true;
        }
        $intensity = trim($intensity);
        foreach ($this->intensityOptions() as $opt) {
            if (($opt['value'] ?? '') === $intensity) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function data(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $path = config_path('tilkit_indicator_behaviour_catalog.json');
        $raw = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
        self::$cache = is_array($raw) ? $raw : ['by_code' => [], 'intensity_options' => []];

        return self::$cache;
    }
}
