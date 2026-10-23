<?php

namespace Tests\Unit;

use App\Enums\Tilkit\ReliabilityRating;
use App\Enums\Tilkit\TercileForecast;
use App\Enums\Tilkit\ValidationStatus;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Services\Tilkit\EligibilityGate;
use PHPUnit\Framework\TestCase;

class TilkitEligibilityGateTest extends TestCase
{
    public function test_eligible_record_passes_gate(): void
    {
        $record = new TilkitIndicatorRecord([
            'sighted' => true,
            'confirmed' => true,
            'approved' => true,
            'validation_status' => ValidationStatus::CommunityPlenaryEndorsed,
            'tercile_forecast' => TercileForecast::AboveNormal,
            'current_reliability' => ReliabilityRating::High,
            'decision_relevance' => ReliabilityRating::High,
        ]);
        $record->setRelation('indicator', new TilkitIndicator(['indicator_uid' => '01-ANI-FRO-001']));

        $result = (new EligibilityGate)->evaluate($record);

        $this->assertTrue($result->eligible);
        $this->assertNull($result->exclusionReason);
    }

    public function test_contested_record_is_excluded(): void
    {
        $record = new TilkitIndicatorRecord([
            'sighted' => true,
            'confirmed' => true,
            'approved' => true,
            'validation_status' => ValidationStatus::Contested,
            'tercile_forecast' => TercileForecast::Normal,
        ]);
        $record->setRelation('indicator', new TilkitIndicator(['indicator_uid' => '01-ANI-FRO-001']));

        $result = (new EligibilityGate)->evaluate($record);

        $this->assertFalse($result->eligible);
        $this->assertSame('contested', $result->exclusionReason);
        $this->assertStringContainsString('Contested', $result->tallyStatusLabel);
    }
}
