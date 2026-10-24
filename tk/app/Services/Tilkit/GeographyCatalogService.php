<?php

namespace App\Services\Tilkit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Continents and countries from a public API (cached in memory/file, not stored in DB).
 */
class GeographyCatalogService
{
    private const CACHE_KEY = 'tilkit.geography.catalog.v3';

    private const MIN_VALID_COUNTRY_COUNT = 100;

    /** @return list<array{code: string, name: string}> */
    public function continents(): array
    {
        $names = [];
        foreach ($this->countries() as $country) {
            foreach ($country['continents'] as $continent) {
                $names[$continent] = true;
            }
        }
        $list = array_keys($names);
        sort($list, SORT_STRING);

        return array_map(fn (string $name) => ['code' => $name, 'name' => $name], $list);
    }

    /** @return list<array{code: string, name: string, continents: list<string>}> */
    public function countries(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && count($cached) >= self::MIN_VALID_COUNTRY_COUNT) {
            return $cached;
        }

        $fresh = $this->fetchPrimaryCountries()
            ?? $this->fetchRestCountriesV5()
            ?? [];

        if (count($fresh) >= self::MIN_VALID_COUNTRY_COUNT) {
            Cache::put(self::CACHE_KEY, $fresh, 86400);

            return $fresh;
        }

        Log::warning('TILKIT geography: live catalog fetch failed; using minimal offline fallback.', [
            'fetched' => count($fresh),
        ]);

        return $fresh !== [] ? $fresh : $this->fallbackCountries();
    }

    /** @return list<array{code: string, name: string}> */
    public function countriesForContinent(string $continent): array
    {
        $needle = Str::lower(trim($continent));

        return array_values(array_map(
            fn (array $c) => ['code' => $c['code'], 'name' => $c['name']],
            array_filter(
                $this->countries(),
                fn (array $c) => collect($c['continents'])->contains(
                    fn (string $cont) => Str::lower($cont) === $needle,
                ),
            ),
        ));
    }

    public function countryName(string $iso2): ?string
    {
        $code = strtoupper(trim($iso2));
        foreach ($this->countries() as $country) {
            if ($country['code'] === $code) {
                return $country['name'];
            }
        }

        return null;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('tilkit.geography.restcountries.v1');
    }

    /** @return list<array{code: string, name: string, continents: list<string>}>|null */
    private function fetchPrimaryCountries(): ?array
    {
        $url = config('tilkit.weather.geography.countries_json_url');
        if (! is_string($url) || $url === '') {
            return null;
        }

        try {
            $response = Http::timeout(30)->acceptJson()->get($url);
            if (! $response->successful()) {
                return null;
            }
            $payload = $response->json();
            if (! is_array($payload)) {
                return null;
            }

            return $this->normalizeDr5hnCountries($payload);
        } catch (\Throwable $e) {
            Log::debug('TILKIT geography primary fetch failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<int, mixed>  $payload
     * @return list<array{code: string, name: string, continents: list<string>}>|null
     */
    private function normalizeDr5hnCountries(array $payload): ?array
    {
        $out = [];
        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = strtoupper((string) ($row['iso2'] ?? ''));
            if (strlen($code) !== 2) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? $code));
            $region = trim((string) ($row['region'] ?? ''));
            if ($region === '') {
                continue;
            }
            $continent = $this->normalizeContinentName($region);
            $out[] = [
                'code' => $code,
                'name' => $name,
                'continents' => [$continent],
            ];
        }

        if ($out === []) {
            return null;
        }

        usort($out, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $out;
    }

    /** @return list<array{code: string, name: string, continents: list<string>}>|null */
    private function fetchRestCountriesV5(): ?array
    {
        $key = config('tilkit.weather.geography.restcountries_api_key');
        $url = config('tilkit.weather.geography.restcountries_v5_url');
        if (! is_string($key) || $key === '' || ! is_string($url) || $url === '') {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withToken($key)
                ->get($url);
            if (! $response->successful()) {
                return null;
            }
            $payload = $response->json();
            $rows = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : null);
            if ($rows === null) {
                return null;
            }

            $out = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $code = strtoupper((string) ($row['cca2'] ?? $row['code'] ?? ''));
                if (strlen($code) !== 2) {
                    continue;
                }
                $name = (string) ($row['name']['common'] ?? $row['name'] ?? $code);
                $continents = array_values(array_filter(
                    (array) ($row['continents'] ?? $row['continent'] ?? []),
                    fn ($c) => is_string($c) && $c !== '',
                ));
                if ($continents === [] && isset($row['region'])) {
                    $continents = [$this->normalizeContinentName((string) $row['region'])];
                }
                if ($continents === []) {
                    continue;
                }
                $out[] = [
                    'code' => $code,
                    'name' => $name,
                    'continents' => array_map(fn (string $c) => $this->normalizeContinentName($c), $continents),
                ];
            }

            usort($out, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            return $out !== [] ? $out : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeContinentName(string $region): string
    {
        $trimmed = trim($region);

        return match (Str::lower($trimmed)) {
            'polar' => 'Antarctica',
            default => $trimmed,
        };
    }

    /** @return list<array{code: string, name: string, continents: list<string>}> */
    private function fallbackCountries(): array
    {
        return [
            ['code' => 'KE', 'name' => 'Kenya', 'continents' => ['Africa']],
            ['code' => 'GH', 'name' => 'Ghana', 'continents' => ['Africa']],
            ['code' => 'TZ', 'name' => 'Tanzania', 'continents' => ['Africa']],
            ['code' => 'UG', 'name' => 'Uganda', 'continents' => ['Africa']],
            ['code' => 'ET', 'name' => 'Ethiopia', 'continents' => ['Africa']],
            ['code' => 'RW', 'name' => 'Rwanda', 'continents' => ['Africa']],
        ];
    }
}
