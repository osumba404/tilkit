<?php

namespace App\Services\KenyaDpa;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSighting;
use App\Models\Tilkit\TilkitWorkbookMedia;
use App\Models\Tilkit\TilkitWorkbookValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TilkitPersonalDataService
{
    /** @return list<string> Column letters treated as personal data for this deployment. */
    public function personalDataColumns(): array
    {
        return array_keys(config('kenya_dpa.tilkit_personal_data_columns', []));
    }

    public function anonymizeRecord(TilkitIndicatorRecord $record, bool $deleteRow = false): void
    {
        DB::transaction(function () use ($record, $deleteRow): void {
            if ($deleteRow) {
                $this->purgeRecordMedia($record);
                $record->workbookValues()->delete();
                $record->sightings()->delete();
                $record->delete();

                return;
            }

            $letters = $this->personalDataColumns();
            TilkitWorkbookValue::query()
                ->where('indicator_record_id', $record->id)
                ->whereIn('column_letter', $letters)
                ->delete();

            foreach ($record->sightings as $sighting) {
                $this->anonymizeSighting($sighting);
            }

            $this->purgeRecordMedia($record);

            $record->consent_level = null;
            $record->consent_credit = null;
            $record->consent_to_share = false;
            $record->processing_restricted = true;
            $record->demographics = null;
            $record->anonymized_at = now();
            $record->save();
        });
    }

    public function applyConsentWithdrawal(TilkitIndicatorRecord $record): void
    {
        if ($record->consent_to_share === false) {
            $record->processing_restricted = true;
            if ($record->consent_withdrawn_at === null) {
                $record->consent_withdrawn_at = now();
            }
            $record->save();
        }
    }

    /** @return int Number of records anonymized */
    public function enforceRetention(DcProject $tool, bool $dryRun = false): int
    {
        $months = $tool->retention_months ?? config('kenya_dpa.default_retention_months');
        if ($months === null || $months < 1) {
            return 0;
        }

        $cutoff = now()->subMonths($months);

        $query = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->whereNull('anonymized_at')
            ->where(function ($q) use ($cutoff): void {
                $q->where('collection_date', '<', $cutoff)
                    ->orWhere('updated_at', '<', $cutoff);
            });

        $count = 0;
        foreach ($query->cursor() as $record) {
            $count++;
            if (! $dryRun) {
                $this->anonymizeRecord($record);
            }
        }

        return $count;
    }

    private function purgeRecordMedia(TilkitIndicatorRecord $record): void
    {
        foreach ($record->workbookMedia as $media) {
            if ($media->storage_path) {
                Storage::delete($media->storage_path);
            }
            $media->delete();
        }
    }

    private function anonymizeSighting(TilkitSighting $sighting): void
    {
        $sighting->update([
            'place_text' => null,
            'landmark' => null,
            'latitude' => null,
            'longitude' => null,
            'observed_by' => null,
        ]);
    }
}
