<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorDictionaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dictionary_page_is_public_and_lists_codes(): void
    {
        $response = $this->get(route('dictionary'));

        $response->assertOk();
        $response->assertSee('Indicator dictionary', false);
        $response->assertSee('01-PLT', false);
        $response->assertSee('Hierarchy vs workbook columns', false);
        $response->assertSee('Browse legend codes', false);
    }
}
