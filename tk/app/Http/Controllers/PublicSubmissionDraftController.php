<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\SubmissionDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSubmissionDraftController extends Controller
{
    public function store(
        Request $request,
        DcProject $tool,
        SubmissionDraftService $drafts,
    ): JsonResponse {
        abort_unless($tool->public_collection_enabled, 404);

        $validated = $request->validate([
            'payload' => ['required', 'array'],
            'form_id' => ['nullable', 'integer'],
        ]);

        $deviceToken = $request->cookie('tk_draft_'.$tool->code);

        $record = $drafts->save(
            $tool,
            $validated['payload'],
            $request->user(),
            $deviceToken,
            $validated['form_id'] ?? null,
        );

        $response = response()->json([
            'saved_at' => $record->updated_at?->toIso8601String(),
        ]);

        if (! $request->user()) {
            $response->cookie('tk_draft_'.$tool->code, $record->device_token, 60 * 24 * 90);
        }

        return $response;
    }

    public function show(Request $request, DcProject $tool, SubmissionDraftService $drafts): JsonResponse
    {
        abort_unless($tool->public_collection_enabled, 404);

        $deviceToken = $request->cookie('tk_draft_'.$tool->code);
        $record = $drafts->load($tool, $request->user(), $deviceToken);

        return response()->json([
            'payload' => $record?->payload ?? [],
        ]);
    }
}
