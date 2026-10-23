<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicatorRecord;

class TilkitConsentGate
{
    /** @return list<string> Human-readable block reasons */
    public function exportBlocks(TilkitIndicatorRecord $record): array
    {
        $blocks = [];
        if ($record->do_no_harm) {
            $blocks[] = 'Do-no-harm flag (BB) — technical review required before dissemination.';
        }
        $level = $record->consent_level;
        if ($level === null || $level < 2) {
            $blocks[] = 'Insufficient consent to share (BC level must be ≥ 2 for export).';
        }

        return $blocks;
    }

    public function canExport(TilkitIndicatorRecord $record): bool
    {
        return $this->exportBlocks($record) === [];
    }
}
