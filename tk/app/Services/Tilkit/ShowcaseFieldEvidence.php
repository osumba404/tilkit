<?php

namespace App\Services\Tilkit;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSighting;
use App\Models\Tilkit\TilkitWorkbookMedia;
use App\Models\User;
use App\Support\Tilkit\WorkbookCapturePreparer;
use Illuminate\Support\Facades\Storage;

class ShowcaseFieldEvidence
{
    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function attachForTool(DcProject $tool, User $owner): void
    {
        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with('workbookValues')
            ->get();

        $png = base64_decode(self::PLACEHOLDER_PNG, true);
        if ($png === false) {
            return;
        }

        $workbook = app(WorkbookRecordService::class);

        foreach ($records as $record) {
            $this->attachPhotos($record, $png, $workbook);
            $this->attachSighting($record, $owner, $workbook);
        }
    }

    private function attachPhotos(TilkitIndicatorRecord $record, string $png, WorkbookRecordService $workbook): void
    {
        if ($record->workbookMedia()->count() >= 3) {
            return;
        }

        for ($i = 1; $i <= 3; $i++) {
            $path = "tilkit/records/{$record->id}/verification-{$i}.png";
            Storage::disk('local')->put($path, $png);

            TilkitWorkbookMedia::query()->create([
                'indicator_record_id' => $record->id,
                'column_letter' => 'Y',
                'storage_path' => $path,
                'mime_type' => 'image/png',
                'sort_order' => $i,
            ]);
        }

        $workbook->storeRawValues($record, ['Y' => 3]);
    }

    private function attachSighting(TilkitIndicatorRecord $record, User $owner, WorkbookRecordService $workbook): void
    {
        $map = $workbook->rawWorkbookMap($record);
        $gps = WorkbookCapturePreparer::splitGps($map['Z'] ?? '');

        $observer = (string) ($map['AB'] ?? 'Community observer circle');
        $observer = preg_replace('/\b(pilot|demo|seed|test)\b/i', 'field', $observer) ?? $observer;

        $payload = [
            'sighted_at' => $this->resolveSightedAt($map),
            'place_text' => (string) ($map['O'] ?? 'Field verification visit'),
            'landmark' => (string) ($map['AA'] ?? 'Community landmark'),
            'latitude' => is_numeric($gps['lat'] ?? null) ? (float) $gps['lat'] : null,
            'longitude' => is_numeric($gps['lng'] ?? null) ? (float) $gps['lng'] : null,
            'observed_by' => $observer,
        ];

        $existing = $record->sightings()->first();
        if ($existing !== null) {
            $existing->update($payload);

            return;
        }

        TilkitSighting::query()->create([
            'indicator_record_id' => $record->id,
            ...$payload,
            'created_by' => $owner->id,
        ]);
    }

    /** @param  array<string, mixed>  $map */
    private function resolveSightedAt(array $map): string
    {
        $ci = $map['CI'] ?? null;
        if (is_string($ci) && preg_match('/^(\d{4}-\d{2}-\d{2})/', $ci, $m)) {
            return $m[1];
        }

        $o = (string) ($map['O'] ?? '');
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $o, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return '2026-10-12';
    }
}
