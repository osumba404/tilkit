<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitWeightReview;
use App\Models\User;

class ClimateWeightReviewService
{
    public function syncFromDerivedFlag(TilkitIndicatorRecord $record, ?string $cp): void
    {
        if ($cp !== 'STRONG') {
            return;
        }

        TilkitWeightReview::query()->firstOrCreate(
            [
                'indicator_record_id' => $record->id,
                'climate_flag' => 'STRONG',
            ],
            ['status' => 'pending']
        );
    }

    public function hasPendingReview(TilkitIndicatorRecord $record): bool
    {
        if (! $record->exists || $record->id === null) {
            return false;
        }

        return TilkitWeightReview::query()
            ->where('indicator_record_id', $record->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function signOff(TilkitWeightReview $review, User $user, ?string $notes = null): void
    {
        $review->update([
            'status' => 'signed_off',
            'signed_off_by' => $user->id,
            'signed_off_at' => now(),
            'notes' => $notes ?? $review->notes,
        ]);
    }
}
