<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\Tilkit\TilkitDeepAnalysisDataset;
use App\Services\ToolAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolTilkitDeepAnalysisController extends Controller
{
    public function show(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        return view('tools.tilkit.deep-analysis', [
            'tool' => $tool,
            'tilkitNav' => 'insights',
            'capabilities' => $request->user()->toolCapabilitiesFor($tool),
            'datasetUrl' => route('tools.tilkit.deep-analysis.dataset', $tool),
            'schemaUrl' => route('tools.tilkit.deep-analysis.schema', $tool),
        ]);
    }

    public function schema(Request $request, DcProject $tool, ToolAccessService $access, TilkitDeepAnalysisDataset $dataset): JsonResponse
    {
        $access->assertCanView($request->user(), $tool);

        return response()->json($dataset->schema($tool));
    }

    public function dataset(Request $request, DcProject $tool, ToolAccessService $access, TilkitDeepAnalysisDataset $dataset): JsonResponse
    {
        $access->assertCanView($request->user(), $tool);

        $data = $request->validate([
            'site_ids' => ['nullable', 'array'],
            'site_ids.*' => ['integer'],
            'season_ids' => ['nullable', 'array'],
            'season_ids.*' => ['integer'],
            'program_id' => ['nullable', 'integer'],
        ]);

        $payload = $dataset->rows($tool, [
            'site_ids' => array_map('intval', $data['site_ids'] ?? []),
            'season_ids' => array_map('intval', $data['season_ids'] ?? []),
            'program_id' => isset($data['program_id']) ? (int) $data['program_id'] : null,
        ]);

        return response()->json($payload);
    }
}
