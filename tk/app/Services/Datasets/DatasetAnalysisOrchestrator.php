<?php

namespace App\Services\Datasets;

use App\Models\PlatformAnalysisRun;
use App\Models\PlatformDataset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DatasetAnalysisOrchestrator
{
    public function __construct(
        private LlmAnalysisCodeGenerator $llm,
        private PythonSandboxExecutor $sandbox,
        private PhpDatasetAnalyzer $phpAnalyzer,
    ) {}

    public function analyze(PlatformDataset $dataset, string $prompt, User $user): PlatformAnalysisRun
    {
        $run = PlatformAnalysisRun::create([
            'platform_dataset_id' => $dataset->id,
            'user_id' => $user->id,
            'prompt' => $prompt,
            'status' => 'running',
        ]);

        $maxAttempts = config('tilkit.dataset_analysis.max_code_fix_attempts', 3);
        $code = $this->llm->generateAnalysisScript($dataset, $prompt);
        $usedPython = false;
        $attempt = 0;

        if ($code !== null && $this->pythonAvailable()) {
            $usedPython = true;
            $attempt = 0;
            $lastError = '';
            while ($attempt < $maxAttempts) {
                $attempt++;
                $result = $this->sandbox->run($dataset, $code);
                if ($result['success']) {
                    $parsed = $this->readWorkspaceOutput($dataset);
                    if ($parsed !== null) {
                        $run->update([
                            'generated_code' => $code,
                            'attempts' => $attempt,
                            'status' => 'completed',
                            'summary' => $parsed['summary'] ?? 'Analysis completed.',
                            'chart_spec' => $parsed['chart'] ?? null,
                            'chart_image_path' => $this->persistChartImage($dataset),
                        ]);

                        return $run->fresh();
                    }
                    $lastError = 'Script ran but output.json was missing or invalid.';
                } else {
                    $lastError = $result['stderr'] ?: $result['stdout'];
                }

                $fixed = $this->llm->fixAnalysisScript($dataset, $prompt, $code, $lastError);
                if (! $fixed) {
                    break;
                }
                $code = $fixed;
            }

            $run->update([
                'generated_code' => $code,
                'attempts' => $attempt,
                'status' => 'failed',
                'error_message' => $lastError,
            ]);
        }

        $fallback = $this->phpAnalyzer->analyze($dataset, $prompt);
        $summary = $fallback['summary'];
        if ($this->llm->isConfigured()) {
            $enhanced = $this->llm->summarizeWithoutCode($prompt, $fallback['chart_spec'] ?? [], $fallback['stats']);
            if ($enhanced) {
                $summary = $enhanced;
            }
        }

        $run->update([
            'status' => 'completed',
            'summary' => $summary,
            'chart_spec' => $fallback['chart_spec'],
            'error_message' => $usedPython ? ($run->error_message ?? 'Python sandbox unavailable or failed; used built-in analyzer.') : null,
        ]);

        return $run->fresh();
    }

    private function pythonAvailable(): bool
    {
        $python = config('tilkit.dataset_analysis.python_binary', 'python');
        $process = new \Symfony\Component\Process\Process([$python, '--version']);
        $process->run();

        return $process->isSuccessful();
    }

    /** @return ?array{summary?: string, chart?: array}> */
    private function readWorkspaceOutput(PlatformDataset $dataset): ?array
    {
        $path = $dataset->workspaceAbsolutePath().DIRECTORY_SEPARATOR.'output.json';
        if (! is_readable($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    private function persistChartImage(PlatformDataset $dataset): ?string
    {
        $src = $dataset->workspaceAbsolutePath().DIRECTORY_SEPARATOR.'chart.png';
        if (! is_readable($src)) {
            return null;
        }

        $dest = $dataset->workspace_path.'/chart_'.time().'.png';
        $destPath = Storage::disk('local')->path($dest);
        copy($src, $destPath);

        return $dest;
    }
}
