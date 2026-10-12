<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsAccToolContext;
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
    use BuildsAccToolContext;

    public function show(Request $request, DcProject $tool, AnalyticsService $analytics, ToolAccessService $access): View
    {
        $access->assertCanViewAnalytics($request->user(), $tool);
        extract($this->accToolContext($request, $tool));

        $overview = $analytics->toolOverview($tool, $filters);
        $trend = $analytics->submissionsTrend($tool, $filters->periodDays, $filters);
        $tercile = $analytics->tercileWeightingDashboard($tool, $filters->site, $filters->season, $filters);
        $calibration = $analytics->calibrationByIndicator($tool);
        $freshness = $analytics->toolDataFreshness($tool);
        $totalDelta = $analytics->kpiDelta($overview['total_submissions'], $overview['compare_total']);
        $accSection = 'overview';

        return view('tools.analytics.overview', compact(
            'tool', 'filters', 'forms', 'overview', 'trend', 'tercile', 'calibration', 'freshness', 'totalDelta', 'accSection',
        ));
    }

    public function explorer(Request $request, DcProject $tool, AnalyticsService $analytics, ToolAccessService $access): View
    {
        $access->assertCanViewAnalytics($request->user(), $tool);
        extract($this->accToolContext($request, $tool));

        $query = $analytics->filteredSubmissionsQuery($tool, $filters)
            ->with(['form:id,title,code', 'collector:id,name'])
            ->latest('received_at');

        $submissions = $query->paginate(25)->withQueryString();
        $accSection = 'explorer';

        return view('tools.analytics.explorer', compact('tool', 'filters', 'forms', 'submissions', 'accSection'));
    }

    public function review(Request $request, DcProject $tool, AnalyticsService $analytics, ToolAccessService $access): View
    {
        $access->assertCanViewAnalytics($request->user(), $tool);
        extract($this->accToolContext($request, $tool));

        $review = $analytics->reviewMetrics($tool, $filters);
        $accSection = 'review';

        return view('tools.analytics.review', compact('tool', 'filters', 'forms', 'review', 'accSection'));
    }

    public function reports(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanViewAnalytics($request->user(), $tool);
        extract($this->accToolContext($request, $tool));

        $scheduledExport = $tool->scheduledExports()->first();
        $capabilities = $request->user()->toolCapabilitiesFor($tool);
        $accSection = 'reports';

        return view('tools.analytics.reports', compact('tool', 'filters', 'forms', 'scheduledExport', 'capabilities', 'accSection'));
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
        $access->assertCanExport($request->user(), $tool);

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
