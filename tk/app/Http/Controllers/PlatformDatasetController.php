<?php

namespace App\Http\Controllers;

use App\Models\PlatformAnalysisRun;
use App\Models\PlatformDataset;
use App\Services\Datasets\DatasetAccService;
use App\Services\Datasets\DatasetAnalysisOrchestrator;
use App\Services\Datasets\DatasetIngestionService;
use App\Support\DatasetAccFilters;
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
