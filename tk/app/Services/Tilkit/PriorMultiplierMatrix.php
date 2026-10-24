<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicator;

/**
 * Literature prior lookup (Brief §4.4): W = 0.40P + 0.30R + 0.30L, normalised to mean 1.0.
 * Order: full code prefix → type → category.
 */
class PriorMultiplierMatrix
{
    /** @var array<string, float> */
    private array $normalizedByKey;

    /** @var array<string, float> */
    private array $explicitByKey;

    /** @var array{p: float, r: float, l: float} */
    private array $compositeWeights;

    public function __construct()
    {
        $path = config_path('tilkit_prior_matrix.json');
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];
        $data = is_array($data) ? $data : [];

        $this->compositeWeights = [
            'p' => (float) ($data['composite_weights']['prevalence'] ?? 0.40),
            'r' => (float) ($data['composite_weights']['reliability'] ?? 0.30),
            'l' => (float) ($data['composite_weights']['lead_time'] ?? 0.30),
        ];

        $this->explicitByKey = [];
        foreach ($data['explicit_multipliers']['types'] ?? [] as $type => $mult) {
            $this->explicitByKey['type:'.strtoupper((string) $type)] = (float) $mult;
        }
        foreach ($data['explicit_multipliers']['codes'] ?? [] as $code => $mult) {
            $this->explicitByKey['code:'.strtoupper((string) $code)] = (float) $mult;
        }

        $rawScores = [];
        foreach ($data['scores']['categories'] ?? [] as $cat => $scores) {
            $rawScores['category:'.str_pad((string) $cat, 2, '0', STR_PAD_LEFT)] = $this->rawComposite($scores);
        }
        foreach ($data['scores']['types'] ?? [] as $type => $scores) {
            $rawScores['type:'.strtoupper((string) $type)] = $this->rawComposite($scores);
        }
        foreach ($data['scores']['codes'] ?? [] as $code => $scores) {
            $rawScores['code:'.strtoupper((string) $code)] = $this->rawComposite($scores);
        }

        $mean = $rawScores !== [] ? array_sum($rawScores) / count($rawScores) : 1.0;
        if ($mean <= 0) {
            $mean = 1.0;
        }

        $this->normalizedByKey = [];
        foreach ($rawScores as $key => $raw) {
            $this->normalizedByKey[$key] = round($raw / $mean, 3);
        }
        foreach ($this->explicitByKey as $key => $mult) {
            $this->normalizedByKey[$key] = round($mult, 3);
        }
    }

    public function forIndicator(?TilkitIndicator $indicator): float
    {
        if ($indicator === null || $indicator->indicator_uid === null || $indicator->indicator_uid === '') {
            return 1.0;
        }

        $parts = explode('-', strtoupper(trim($indicator->indicator_uid)));
        $category = str_pad($parts[0] ?? '', 2, '0', STR_PAD_LEFT);
        $typeKey = count($parts) >= 2 ? $category.'-'.($parts[1] ?? '') : $category;
        $codePrefix = count($parts) >= 3
            ? $category.'-'.($parts[1] ?? '').'-'.($parts[2] ?? '')
            : $typeKey;

        foreach ([
            'code:'.$codePrefix,
            'type:'.$typeKey,
            'category:'.$category,
        ] as $lookup) {
            if (isset($this->normalizedByKey[$lookup])) {
                return $this->normalizedByKey[$lookup];
            }
        }

        return 1.0;
    }

    /** @param  array{p?: mixed, r?: mixed, l?: mixed}  $scores */
    private function rawComposite(array $scores): float
    {
        $p = (float) ($scores['p'] ?? 2);
        $r = (float) ($scores['r'] ?? 2);
        $l = (float) ($scores['l'] ?? 2);

        return $this->compositeWeights['p'] * $p
            + $this->compositeWeights['r'] * $r
            + $this->compositeWeights['l'] * $l;
    }
}
