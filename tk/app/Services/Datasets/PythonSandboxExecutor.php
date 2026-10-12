<?php

namespace App\Services\Datasets;

use App\Models\PlatformDataset;
use Symfony\Component\Process\Process;

class PythonSandboxExecutor
{
    /** @return array{success: bool, stdout: string, stderr: string, duration_ms: int} */
    public function run(PlatformDataset $dataset, string $code): array
    {
        if (! $this->validateCode($code)) {
            return [
                'success' => false,
                'stdout' => '',
                'stderr' => 'Generated code failed safety checks.',
                'duration_ms' => 0,
            ];
        }

        $workspace = $dataset->workspaceAbsolutePath();
        $scriptPath = $workspace.DIRECTORY_SEPARATOR.'analysis_'.time().'.py';
        file_put_contents($scriptPath, $code);

        $python = config('tilkit.dataset_analysis.python_binary', 'python');
        $timeout = config('tilkit.dataset_analysis.sandbox_timeout_seconds', 45);

        $process = new Process([$python, $scriptPath], $workspace, [
            'MPLBACKEND' => 'Agg',
            'PYTHONIOENCODING' => 'utf-8',
        ], null, $timeout);

        $start = microtime(true);
        $process->run();
        $durationMs = (int) ((microtime(true) - $start) * 1000);

        @unlink($scriptPath);

        return [
            'success' => $process->isSuccessful(),
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'duration_ms' => $durationMs,
        ];
    }

    public function validateCode(string $code): bool
    {
        $blocked = ['subprocess', 'os.system', 'os.popen', 'socket.', 'requests', 'urllib', 'shutil.rmtree', '__import__', 'eval(', 'exec(', 'compile('];
        $lower = strtolower($code);
        foreach ($blocked as $needle) {
            if (str_contains($lower, $needle)) {
                return false;
            }
        }

        return str_contains($lower, 'pandas') && str_contains($lower, 'read_csv');
    }
}
