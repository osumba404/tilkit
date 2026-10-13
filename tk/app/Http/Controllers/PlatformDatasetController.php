<?php

namespace App\Http\Controllers;

use App\Models\PlatformAnalysisRun;
use App\Models\PlatformDataset;
use App\Services\Datasets\DatasetAccService;
use App\Services\Datasets\DatasetAnalysisOrchestrator;
use App\Services\Datasets\DatasetIngestionService;
use App\Support\DatasetAccFilters;
use App\Support\DatasetChartSchema;
use App\Support\DatasetPanelSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlatformDatasetController extends Controller
{
    public function index(Request $request): View
    {
        $datasets = PlatformDataset::query()
            ->where('uploaded_by', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('platform.datasets.index', compact('datasets'));
    }

    public function store(Request $request, DatasetIngestionService $ingestion): RedirectResponse
    {
        $maxMb = config('tilkit.dataset_analysis.max_upload_mb', 25);

        $validated = $request->validate([
            'dataset' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:'.($maxMb * 1024)],
            'name' => ['nullable', 'string', 'max:200'],
        ]);

        $dataset = $ingestion->ingest($validated['dataset'], $request->user(), $validated['name'] ?? null);

        return redirect()
            ->route('platform.datasets.show', $dataset)
            ->with('status', 'Dataset uploaded. Explore the Analytics Command Center dashboard or ask AI a question.');
    }

    public function show(Request $request, PlatformDataset $dataset, DatasetAccService $acc): View
    {
        $dataset = $this->authorizedDataset($request, $dataset);
        $filters = DatasetAccFilters::fromRequest($request, $dataset);
        $overview = $acc->overview($dataset, $filters);
        $chartPayloads = $acc->materializeCharts($dataset, $filters);
        $accSection = 'overview';

        return view('platform.datasets.dashboard', compact(
            'dataset', 'filters', 'overview', 'chartPayloads', 'accSection',
        ));
    }

    public function explorer(Request $request, PlatformDataset $dataset, DatasetAccService $acc): View
    {
        $dataset = $this->authorizedDataset($request, $dataset);
        $filters = DatasetAccFilters::fromRequest($request, $dataset);
        $rows = $acc->paginateRows($dataset, $filters, max(1, (int) $request->input('page', 1)));
        $headers = $dataset->columns->pluck('name')->all();
        $accSection = 'explorer';

        return view('platform.datasets.explorer', compact('dataset', 'filters', 'rows', 'headers', 'accSection'));
    }

    public function analyze(Request $request, PlatformDataset $dataset): View
    {
        $dataset = $this->authorizedDataset($request, $dataset);
        $dataset->load(['columns', 'analysisRuns' => fn ($q) => $q->limit(20)->with('user:id,name')]);
        $llmConfigured = filled(config('tilkit.openai.api_key'));
        $accSection = 'analyze';

        return view('platform.datasets.analyze', compact('dataset', 'llmConfigured', 'accSection'));
    }

    public function runAnalyze(
        Request $request,
        PlatformDataset $dataset,
        DatasetAnalysisOrchestrator $orchestrator,
    ): RedirectResponse {
        $this->authorizeDataset($request, $dataset);

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $orchestrator->analyze($dataset, $validated['prompt'], $request->user());

        return redirect()
            ->route('platform.datasets.analyze', $dataset)
            ->with('status', 'Analysis complete.');
    }

    public function storePanel(Request $request, PlatformDataset $dataset, DatasetAccService $acc): RedirectResponse|JsonResponse
    {
        $this->authorizeDataset($request, $dataset);

        $definition = DatasetPanelSession::definitionFromRequest($request, $dataset);
        $error = DatasetChartSchema::validate($definition);
        if ($error !== null) {
            return $this->panelMutationResponse($request, $dataset, $acc, false, ['panel' => $error], 422);
        }

        if (! DatasetPanelSession::append($request, $dataset, $definition)) {
            return $this->panelMutationResponse($request, $dataset, $acc, false, ['panel' => 'Maximum number of panels reached. Remove one to add another.'], 422);
        }

        return $this->panelMutationResponse($request, $dataset, $acc, true, message: 'Visualization added.', focusPanelId: $definition['id']);
    }

    public function updatePanel(Request $request, PlatformDataset $dataset, string $panel, DatasetAccService $acc): RedirectResponse|JsonResponse
    {
        $this->authorizeDataset($request, $dataset);

        if (DatasetPanelSession::find($request, $dataset, $panel) === null) {
            abort(404);
        }

        $definition = DatasetPanelSession::definitionFromRequest($request, $dataset, $panel);
        $error = DatasetChartSchema::validate($definition);
        if ($error !== null) {
            return $this->panelMutationResponse($request, $dataset, $acc, false, ['panel' => $error], 422);
        }

        DatasetPanelSession::update($request, $dataset, $panel, $definition);

        return $this->panelMutationResponse($request, $dataset, $acc, true, message: 'Panel updated.', focusPanelId: $panel);
    }

    public function destroyPanel(Request $request, PlatformDataset $dataset, string $panel, DatasetAccService $acc): RedirectResponse|JsonResponse
    {
        $this->authorizeDataset($request, $dataset);
        DatasetPanelSession::remove($request, $dataset, $panel);

        return $this->panelMutationResponse($request, $dataset, $acc, true, message: 'Panel removed.');
    }

    public function clearPanels(Request $request, PlatformDataset $dataset, DatasetAccService $acc): RedirectResponse|JsonResponse
    {
        $this->authorizeDataset($request, $dataset);
        DatasetPanelSession::clear($request, $dataset);

        return $this->panelMutationResponse($request, $dataset, $acc, true, message: 'All panels cleared.');
    }

    /** @param  array<string, string>  $errors */
    private function panelMutationResponse(
        Request $request,
        PlatformDataset $dataset,
        DatasetAccService $acc,
        bool $ok,
        array $errors = [],
        int $errorStatus = 422,
        ?string $focusPanelId = null,
        ?string $message = null,
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            if (! $ok) {
                return response()->json(['message' => $errors['panel'] ?? 'Validation failed.', 'errors' => $errors], $errorStatus);
            }

            return response()->json($this->dashboardPanelsPayload($request, $dataset, $acc, $focusPanelId, $message));
        }

        if (! $ok) {
            return back()->withInput()->withErrors($errors);
        }

        $url = $this->datasetDashboardUrl($dataset, $request);
        if ($focusPanelId) {
            $url .= '#panel-'.$focusPanelId;
        }

        return redirect()->to($url)->with('status', $message ?? 'Done.');
    }

    /** @return array<string, mixed> */
    private function dashboardPanelsPayload(
        Request $request,
        PlatformDataset $dataset,
        DatasetAccService $acc,
        ?string $focusPanelId = null,
        ?string $message = null,
    ): array {
        $dataset = $this->authorizedDataset($request, $dataset);
        $filters = DatasetAccFilters::fromRequest($request, $dataset);
        $chartPayloads = $acc->materializeCharts($dataset, $filters);

        return [
            'message' => $message,
            'focus_panel_id' => $focusPanelId,
            'panel_count' => count($chartPayloads),
            'charts' => $chartPayloads,
            'panels_html' => view('analytics.partials.dataset-panels-stack', [
                'chartPayloads' => $chartPayloads,
                'dataset' => $dataset,
                'filters' => $filters,
            ])->render(),
            'add_panel_html' => view('analytics.partials.dataset-add-panel', [
                'filters' => $filters,
                'dataset' => $dataset,
            ])->render(),
        ];
    }

    private function datasetDashboardUrl(PlatformDataset $dataset, Request $request): string
    {
        $query = array_filter([
            'filter_column' => $request->input('filter_column'),
            'filter_value' => $request->input('filter_value'),
        ], fn ($v) => $v !== null && $v !== '');

        $url = route('platform.datasets.show', $dataset);

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    public function chart(Request $request, PlatformDataset $dataset, PlatformAnalysisRun $run): BinaryFileResponse
    {
        $this->authorizeDataset($request, $dataset);
        abort_unless($run->platform_dataset_id === $dataset->id, 404);
        abort_unless($run->chart_image_path && Storage::disk('local')->exists($run->chart_image_path), 404);

        $path = Storage::disk('local')->path($run->chart_image_path);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="chart.png"',
        ]);
    }

    private function authorizedDataset(Request $request, PlatformDataset $dataset): PlatformDataset
    {
        $this->authorizeDataset($request, $dataset);

        return $dataset->load('columns');
    }

    private function authorizeDataset(Request $request, PlatformDataset $dataset): void
    {
        abort_unless($dataset->uploaded_by === $request->user()->id, 403);
    }
}
