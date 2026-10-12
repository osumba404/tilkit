<?php

namespace App\Http\Controllers;

use App\Models\PlatformAnalysisRun;
use App\Models\PlatformDataset;
use App\Services\Datasets\DatasetAnalysisOrchestrator;
use App\Services\Datasets\DatasetIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformDatasetController extends Controller
{
    public function index(): View
    {
        $datasets = PlatformDataset::query()
            ->with('uploader:id,name')
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
            ->with('status', 'Dataset uploaded and profiled. Ask a question in plain English to analyze it.');
    }

    public function show(PlatformDataset $dataset): View
    {
        $dataset->load(['columns', 'analysisRuns' => fn ($q) => $q->limit(20)->with('user:id,name')]);

        $llmConfigured = filled(config('tilkit.openai.api_key'));

        return view('platform.datasets.show', compact('dataset', 'llmConfigured'));
    }

    public function analyze(
        Request $request,
        PlatformDataset $dataset,
        DatasetAnalysisOrchestrator $orchestrator,
    ): RedirectResponse {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $orchestrator->analyze($dataset, $validated['prompt'], $request->user());

        return back()->with('status', 'Analysis complete.');
    }

    public function chart(PlatformDataset $dataset, PlatformAnalysisRun $run): StreamedResponse
    {
        abort_unless($run->platform_dataset_id === $dataset->id, 404);
        abort_unless($run->chart_image_path && Storage::disk('local')->exists($run->chart_image_path), 404);

        return Storage::disk('local')->download($run->chart_image_path, 'chart.png');
    }
}
