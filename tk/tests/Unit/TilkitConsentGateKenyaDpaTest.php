<?php

namespace Tests\Unit;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Services\Tilkit\TilkitConsentGate;
use PHPUnit\Framework\TestCase;

class TilkitConsentGateKenyaDpaTest extends TestCase
{
    public function test_withdrawn_consent_blocks_export(): void
    {
        $record = new TilkitIndicatorRecord([
            'consent_level' => 3,
            'consent_to_share' => false,
            'do_no_harm' => false,
            'processing_restricted' => true,
        ]);

        $gate = new TilkitConsentGate;

        $this->assertFalse($gate->canExport($record));
        $this->assertStringContainsString('declined sharing', implode(' ', $gate->exportBlocks($record)));
    }
}
