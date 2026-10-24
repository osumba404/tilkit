<?php

namespace Tests\Unit;

use App\Services\Tilkit\GeographyCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TilkitGeographyCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_full_continents_and_african_countries_from_primary_source(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response($this->sampleCountryRows(), 200),
        ]);

        app(GeographyCatalogService::class)->clearCache();

        $geo = app(GeographyCatalogService::class);
        $continents = collect($geo->continents())->pluck('name')->all();

        $this->assertContains('Africa', $continents);
        $this->assertContains('Asia', $continents);
        $this->assertContains('Europe', $continents);
        $this->assertContains('Americas', $continents);
        $this->assertGreaterThanOrEqual(5, count($continents));

        $africa = $geo->countriesForContinent('Africa');
        $this->assertGreaterThanOrEqual(2, count($africa));
        $this->assertTrue(collect($africa)->contains(fn ($c) => $c['code'] === 'KE'));
    }

    public function test_does_not_cache_degraded_fallback_as_full_catalog(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        app(GeographyCatalogService::class)->clearCache();
        $first = app(GeographyCatalogService::class)->countries();
        $this->assertLessThan(20, count($first));

        Http::fake([
            'raw.githubusercontent.com/*' => Http::response($this->sampleCountryRows(), 200),
        ]);

        $second = app(GeographyCatalogService::class)->countries();
        $this->assertGreaterThanOrEqual(3, count($second));
    }

    /** @return list<array<string, mixed>> */
    private function sampleCountryRows(): array
    {
        return [
            ['name' => 'Kenya', 'iso2' => 'KE', 'region' => 'Africa'],
            ['name' => 'Ghana', 'iso2' => 'GH', 'region' => 'Africa'],
            ['name' => 'Japan', 'iso2' => 'JP', 'region' => 'Asia'],
            ['name' => 'France', 'iso2' => 'FR', 'region' => 'Europe'],
            ['name' => 'Brazil', 'iso2' => 'BR', 'region' => 'Americas'],
            ['name' => 'Australia', 'iso2' => 'AU', 'region' => 'Oceania'],
        ];
    }
}
