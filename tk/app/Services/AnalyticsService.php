<?php

namespace App\Services;

use App\Models\DcFormField;
use App\Models\DcProject;
use App\Models\DcSubmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function toolOverview(DcProject $project): array
    {
        $submissions = DcSubmission::query()->where('project_id', $project->id);

        return [
            'total_submissions' => (clone $submissions)->count(),
            'submissions_today' => (clone $submissions)->whereDate('received_at', today())->count(),
            'by_status' => (clone $submissions)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'by_channel' => (clone $submissions)
                ->select('channel', DB::raw('count(*) as total'))
                ->groupBy('channel')
                ->pluck('total', 'channel'),
            'forms_count' => $project->forms()->count(),
            'published_forms' => $project->forms()->where('status', 'published')->count(),
        ];
    }

    /** @return array<int, array{date: string, count: int}> */
    public function submissionsTrend(DcProject $project, int $days = 30): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = DcSubmission::query()
            ->where('project_id', $project->id)
            ->where('received_at', '>=', $from)
            ->select(DB::raw('DATE(received_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $trend[] = ['date' => $day, 'count' => (int) ($rows[$day] ?? 0)];
        }

        return $trend;
    }

    /** @return Collection<int, object> */
    public function fieldBreakdown(DcProject $project, int $formId, string $fieldName): Collection
    {
        $field = DcFormField::query()
            ->whereHas('formVersion.form', fn ($q) => $q->where('project_id', $project->id)->where('id', $formId))
            ->where('name', $fieldName)
            ->latest('id')
            ->first();

        if (! $field) {
            return collect();
        }

        if (in_array($field->field_type, ['integer', 'decimal', 'range'], true)) {
            return DB::table('dc_answers')
                ->join('dc_submissions', 'dc_submissions.id', '=', 'dc_answers.submission_id')
                ->where('dc_submissions.project_id', $project->id)
                ->where('dc_answers.field_id', $field->id)
                ->whereNotNull('dc_answers.value_number')
                ->select('dc_answers.value_number as bucket', DB::raw('count(*) as total'))
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();
        }

        return DB::table('dc_answers')
            ->join('dc_submissions', 'dc_submissions.id', '=', 'dc_answers.submission_id')
            ->where('dc_submissions.project_id', $project->id)
            ->where('dc_answers.field_id', $field->id)
            ->whereNotNull('dc_answers.value_text')
            ->select('dc_answers.value_text as bucket', DB::raw('count(*) as total'))
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->limit(20)
            ->get();
    }

    public function platformStats(): array
    {
        return [
            'users' => DB::table('users')->whereNull('deleted_at')->count(),
            'tools' => DB::table('dc_projects')->whereNull('deleted_at')->count(),
            'forms' => DB::table('dc_forms')->whereNull('deleted_at')->count(),
            'submissions' => DB::table('dc_submissions')->whereNull('deleted_at')->count(),
            'submissions_7d' => DB::table('dc_submissions')
                ->whereNull('deleted_at')
                ->where('received_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }
}
