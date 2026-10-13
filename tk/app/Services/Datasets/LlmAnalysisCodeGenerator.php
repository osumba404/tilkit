<?php

namespace App\Services\Datasets;

use App\Models\PlatformDataset;
use Illuminate\Support\Facades\Http;

class LlmAnalysisCodeGenerator
{
    public function isConfigured(): bool
    {
        return filled(config('tilkit.openai.api_key'));
    }

    public function generateAnalysisScript(PlatformDataset $dataset, string $prompt): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $schema = $dataset->columns->map(fn ($c) => [
            'name' => $c->name,
            'type' => $c->inferred_type,
            'samples' => $c->sample_values,
        ])->values()->all();

        $system = <<<'PROMPT'
You write Python 3 analysis scripts for a sandboxed workspace. Rules:
- The CSV file is always named data.csv in the current working directory.
- Use pandas for data work and matplotlib (Agg backend) for charts.
- Save a JSON file output.json with keys: summary (string, plain English for the user), chart (object with type bar|pie|line|scatter|histogram, labels+values for bar/pie/line, or points [{x,y}] for scatter, OR null if no chart).
- If a chart is requested, also save chart.png (matplotlib figure).
- Do not import subprocess, socket, requests, or os.system.
- Only read data.csv; write only output.json and chart.png.
- Handle missing values safely.
Return ONLY Python code, no markdown fences.
PROMPT;

        $user = "Dataset columns:\n".json_encode($schema, JSON_PRETTY_PRINT)
            ."\n\nRow count: {$dataset->row_count}\n\nUser request: {$prompt}";

        return $this->chat($system, $user);
    }

    public function fixAnalysisScript(PlatformDataset $dataset, string $prompt, string $code, string $error): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $system = 'Fix the Python script. Same output rules: data.csv input, output.json + optional chart.png. Return ONLY fixed Python code.';

        $user = "Original request: {$prompt}\n\nFailed code:\n{$code}\n\nError:\n{$error}";

        return $this->chat($system, $user);
    }

    public function summarizeWithoutCode(string $prompt, array $chartSpec, array $stats): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $system = 'Write a short plain-English summary (3-5 sentences) of analysis results for a non-technical user. No markdown.';

        $user = "User asked: {$prompt}\nChart spec: ".json_encode($chartSpec)."\nStats: ".json_encode($stats);

        return $this->chat($system, $user);
    }

    private function chat(string $system, string $user): ?string
    {
        $response = Http::withToken(config('tilkit.openai.api_key'))
            ->timeout(90)
            ->post(rtrim(config('tilkit.openai.base_url'), '/').'/chat/completions', [
                'model' => config('tilkit.openai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.2,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $content = $response->json('choices.0.message.content');

        return $this->stripCodeFences((string) $content);
    }

    private function stripCodeFences(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:python)?\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        }

        return trim($text);
    }
}
