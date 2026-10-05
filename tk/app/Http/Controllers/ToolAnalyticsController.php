<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Models\DcProject;
use App\Models\DcSubmission;
use App\Services\AnalyticsService;
use App\Services\ToolAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolAnalyticsController extends Controller
{
    public function show(Request $request, DcProject $tool, AnalyticsService $analytics, ToolAccessService $access): View
    {
        $access->assertCanViewAnalytics($request->user(), $tool);

        $overview = $analytics->toolOverview($tool);
        $trend = $analytics->submissionsTrend($tool);
        $forms = $tool->forms()->where('status', 'published')->get(['id', 'title', 'code']);

        return view('tools.analytics', compact('tool', 'overview', 'trend', 'forms'));
    }

    public function fieldBreakdown(Request $request, DcProject $tool, AnalyticsService $analytics, ToolAccessService $access)
    {
        $access->assertCanViewAnalytics($request->user(), $tool);

        $validated = $request->validate([
            'form_id' => ['required', 'integer'],
            'field_name' => ['required', 'string'],
        ]);

        return response()->json(
            $analytics->fieldBreakdown($tool, (int) $validated['form_id'], $validated['field_name'])
        );
    }

    public function export(Request $request, DcProject $tool, ToolAccessService $access): StreamedResponse
    {
        $access->assertCanManage($request->user(), $tool);

        $filename = 'tilkit-'.$tool->code.'-submissions-'.now()->format('Y-m-d').'.csv';

        return Response::streamDownload(function () use ($tool): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['client_uuid', 'form_code', 'status', 'channel', 'received_at', 'data_json']);

            DcSubmission::query()
                ->where('project_id', $tool->id)
                ->with('form:id,code')
                ->orderBy('id')
                ->chunk(200, function ($chunk) use ($handle): void {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->client_uuid,
                            $row->form?->code,
                            $row->status,
                            $row->channel,
                            $row->received_at?->toIso8601String(),
                            json_encode($row->data),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
