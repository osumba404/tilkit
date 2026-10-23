<?php

namespace App\Services\Tilkit;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TilkitSignOffGuard
{
    public function applySignOff(TilkitIndicatorRecord $record, User $user, array $workbook): array
    {
        $enforceSeparation = (bool) config('tilkit.weather.engine.separate_confirm_approve', true);

        if (array_key_exists('AC', $workbook) && $this->isYes($workbook['AC'])) {
            if ($enforceSeparation && $record->approved_by_user_id === $user->id) {
                throw ValidationException::withMessages([
                    'workbook.AC' => 'The same person cannot confirm and approve this record.',
                ]);
            }
            $record->confirmed = true;
            $record->confirmed_by_user_id = $user->id;
        }

        if (array_key_exists('AD', $workbook) && $this->isYes($workbook['AD'])) {
            if ($enforceSeparation && $record->confirmed_by_user_id === $user->id) {
                throw ValidationException::withMessages([
                    'workbook.AD' => 'The same person cannot confirm and approve this record.',
                ]);
            }
            $record->approved = true;
            $record->approved_by_user_id = $user->id;
        }

        if (array_key_exists('AE', $workbook) && $this->isYes($workbook['AE'])) {
            $record->verified = true;
            $record->verified_by_user_id = $user->id;
        }

        $record->save();

        return $workbook;
    }

    private function isYes(mixed $value): bool
    {
        return in_array($value, [1, '1', true, 'Yes', 'yes'], true);
    }
}
