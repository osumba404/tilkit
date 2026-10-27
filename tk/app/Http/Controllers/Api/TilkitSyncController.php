<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSighting;
use App\Services\Tilkit\WorkbookRecordService;
use App\Services\ToolAccessService;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Idempotent sighting sync for field apps (Brief §8 - web MVP). */
class TilkitSyncController extends Controller
{
    public function storeSighting(Request $request, ToolAccessService $access, WorkbookRecordService $workbook): JsonResponse
    {
        $tool = WeatherTool::resolve();
        abort_if($tool === null, 503, 'Weather tool not configured.');
        $access->assertCanView($request->user(), $tool);

        $data = $request->validate([
            'record_uuid' => ['required', 'uuid'],
            'sighting_uuid' => ['required', 'uuid'],
            'sighted_at' => ['required', 'date'],
            'place_text' => ['nullable', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:200'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'observed_by' => ['nullable', 'string', 'max:200'],
        ]);

        $record = TilkitIndicatorRecord::query()->where('uuid', $data['record_uuid'])->firstOrFail();
        abort_unless($record->indicator?->project_id === $tool->id, 404);

        $sighting = TilkitSighting::query()->firstOrCreate(
            ['uuid' => $data['sighting_uuid']],
            [
                'indicator_record_id' => $record->id,
                'sighted_at' => $data['sighted_at'],
                'place_text' => $data['place_text'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'observed_by' => $data['observed_by'] ?? null,
                'created_by' => $request->user()->id,
            ]
        );

        $record->update(['sighted' => true]);
        $gps = (isset($data['latitude'], $data['longitude']) && $data['latitude'] !== null && $data['longitude'] !== null)
            ? trim((string) $data['latitude']).','.trim((string) $data['longitude'])
            : null;

        $workbook->storeRawValues($record, array_filter([
            'N' => 1,
            'O' => $data['place_text'] ?? null,
            'CI' => $data['sighted_at'],
            'Z' => $gps,
            'AA' => $data['landmark'] ?? null,
            'AB' => $data['observed_by'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        return response()->json(['status' => 'ok', 'sighting_uuid' => $sighting->uuid]);
    }
}
