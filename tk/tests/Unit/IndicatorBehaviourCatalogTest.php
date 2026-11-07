<?php

namespace Tests\Unit;

use App\Models\Tilkit\TilkitIndicatorCode;
use App\Support\Tilkit\IndicatorBehaviourCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndicatorBehaviourCatalogTest extends TestCase
{
    public function test_plt_flowering_behaviours_are_plant_specific(): void
    {
        $catalog = app(IndicatorBehaviourCatalog::class);
        $code = new TilkitIndicatorCode([
            'code' => '01-PLT-FLW',
            'category' => '01',
            'type' => 'PLT',
            'sub_type' => 'FLW',
            'label' => 'Flowering/blooming',
        ]);

        $list = $catalog->behavioursForCode($code);
        $this->assertNotEmpty($list);
        $joined = strtolower(implode(' ', $list));
        $this->assertStringContainsString('blossom', $joined);
        $this->assertStringNotContainsString('bird', $joined);
        $this->assertStringNotContainsString('flying east', $joined);
    }

    public function test_hyd_flow_code_is_not_plant_flowering(): void
    {
        $catalog = app(IndicatorBehaviourCatalog::class);
        $code = new TilkitIndicatorCode([
            'code' => '04-HYD-FLW',
            'category' => '04',
            'type' => 'HYD',
            'sub_type' => 'FLW',
            'label' => 'Water flow/current/spring discharge',
        ]);

        $list = $catalog->behavioursForCode($code);
        $joined = strtolower(implode(' ', $list));
        $this->assertStringContainsString('spring', $joined);
        $this->assertStringNotContainsString('blossom', $joined);
    }

    public function test_compose_and_parse_column_d(): void
    {
        $catalog = app(IndicatorBehaviourCatalog::class);
        $d = $catalog->composeColumnD('Heavy blossoming / peak bloom', 'High');
        $this->assertSame('Heavy blossoming / peak bloom | Intensity: High', $d);
        $parsed = $catalog->parseColumnD($d);
        $this->assertSame('Heavy blossoming / peak bloom', $parsed['behaviour']);
        $this->assertSame('High', $parsed['intensity']);
    }

    public function test_catalog_covers_all_legend_codes_in_config(): void
    {
        $path = config_path('tilkit_indicator_behaviour_catalog.json');
        $this->assertFileExists($path);
        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data);
        $byCode = $data['by_code'] ?? [];
        $this->assertGreaterThanOrEqual(150, count($byCode));

        foreach ($byCode as $code => $entry) {
            $this->assertNotEmpty($entry['behaviours'] ?? [], "Missing behaviours for {$code}");
        }
    }

    #[DataProvider('crossDomainForbiddenProvider')]
    public function test_cross_domain_tokens_are_absent(string $legendCode, array $forbidden): void
    {
        $catalog = app(IndicatorBehaviourCatalog::class);
        $behaviours = $catalog->behavioursForLegendCode($legendCode);
        $blob = strtolower(implode(' ', $behaviours));
        foreach ($forbidden as $token) {
            $this->assertStringNotContainsString(strtolower($token), $blob, "Legend {$legendCode}");
        }
    }

    /** @return array<string, array{0: string, 1: list<string>}> */
    public static function crossDomainForbiddenProvider(): array
    {
        return [
            'plant flowering' => ['01-PLT-FLW', ['bird', 'flying east', 'thunder']],
            'bird migration' => ['01-ANI-BMG', ['blossom', 'flowering plant', 'leaf flush']],
            'atmosphere cloud' => ['02-ATM-CLD', ['frog croak', 'intestine']],
        ];
    }
}
