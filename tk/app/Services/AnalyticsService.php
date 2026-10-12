<?php

namespace App\Services;

use App\Models\DcFormField;
use App\Models\DcProject;
use App\Models\DcSubmission;
use App\Support\AccFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /** @return Builder<DcSubmission> */
    public function filteredSubmissionsQuery(DcProject $project, AccFilters $filters): Builder
    {
        $query = DcSubmission::query()
            ->where('project_id', $project->id)
            ->where('received_at', '>=', $filters->periodStart())
            ->where('received_at', '<=', $filters->periodEnd());

        if ($filters->formId) {
            $query->where('form_id', $filters->formId);
        }
        if ($filters->status) {
            $query->where('status', $filters->status);
        }
        if ($filters->channel) {
            $query->where('channel', $filters->channel);
        }

        return $query;
    }

    public function toolOverview(DcProject $project, ?AccFilters $filters = null): array
    {
        $filters ??= new AccFilters(30, 'previous', null, null, null, null, null, 'daily');
        $submissions = $this->filteredSubmissionsQuery($project, $filters);

        $approved = (clone $submissions)->where('status', 'approved')->count();
        $decided = (clone $submissions)->whereIn('status', ['approved', 'rejected'])->count();

        return [
            'total_submissions' => (clone $submissions)->count(),
            'submissions_today' => DcSubmission::query()
                ->where('project_id', $project->id)
                ->whereDate('received_at', today())
                ->count(),
            'in_review' => (clone $submissions)->whereIn('status', ['submitted', 'in_review'])->count(),
            'approval_rate' => $decided > 0 ? round(100 * $approved / $decided, 1) : null,
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
            'compare_total' => $this->compareSubmissionCount($project, $filters),
            'last_updated' => DcSubmission::query()
                ->where('project_id', $project->id)
                ->max('received_at'),
        ];
    }

    public function compareSubmissionCount(DcProject $project, AccFilters $filters): int
    {
        if ($filters->compare === 'none') {
            return 0;
        }

        return DcSubmission::query()
            ->where('project_id', $project->id)
            ->where('received_at', '>=', $filters->previousPeriodStart())
            ->where('received_at', '<=', $filters->previousPeriodEnd())
            ->when($filters->formId, fn ($q) => $q->where('form_id', $filters->formId))
            ->when($filters->status, fn ($q) => $q->where('status', $filters->status))
            ->when($filters->channel, fn ($q) => $q->where('channel', $filters->channel))
            ->count();
    }

    /** @return array{current: int, previous: int, change_pct: ?float} */
    public function kpiDelta(int $current, int $previous): array
    {
        $change = null;
        if ($previous > 0) {
            $change = round(100 * ($current - $previous) / $previous, 1);
        } elseif ($current > 0) {
            $change = 100.0;
        }

        return ['current' => $current, 'previous' => $previous, 'change_pct' => $change];
    }

    /** @return array<int, array{date: string, count: int}> */
    public function submissionsTrend(DcProject $project, int $days = 30, ?AccFilters $filters = null): array
    {
        $filters ??= new AccFilters($days, 'previous', null, null, null, null, null, 'daily');
        $from = $filters->periodStart();

        $rows = $this->filteredSubmissionsQuery($project, $filters)
            ->select(DB::raw('DATE(received_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $dayCount = $filters->periodDays;
        $trend = [];
        for ($i = 0; $i < $dayCount; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            if ($day > $filters->periodEnd()->toDateString()) {
                break;
            }
            $trend[] = ['date' => $day, 'count' => (int) ($rows[$day] ?? 0)];
        }

        return $trend;
    }

    /** @return array<string, mixed> */
    public function reviewMetrics(DcProject $project, AccFilters $filters): array
    {
        $base = $this->filteredSubmissionsQuery($project, $filters);

        return [
            'submitted' => (clone $base)->where('status', 'submitted')->count(),
            'in_review' => (clone $base)->where('status', 'in_review')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function toolDataFreshness(DcProject $project): array
    {
        $lastSubmission = DcSubmission::query()->where('project_id', $project->id)->max('received_at');
        $export = $project->scheduledExports()->where('is_active', true)->first();

        return [
            'database' => [
                'label' => 'Tilkit database',
                'status' => 'connected',
                'updated_at' => $lastSubmission,
            ],
            'scheduled_export' => [
                'label' => 'Scheduled CSV export',
                'status' => $export ? 'active' : 'off',
                'updated_at' => $export?->last_run_at,
            ],
            'public_collection' => [
                'label' => 'Public collection',
                'status' => $project->public_collection_enabled ? 'enabled' : 'disabled',
                'updated_at' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function platformOverview(AccFilters $filters): array
    {
        $query = DcSubmission::query()
            ->whereNull('deleted_at')
            ->where('received_at', '>=', $filters->periodStart())
            ->where('received_at', '<=', $filters->periodEnd());

        $current = (clone $query)->count();
        $previous = DcSubmission::query()
            ->whereNull('deleted_at')
            ->where('received_at', '>=', $filters->previousPeriodStart())
            ->where('received_at', '<=', $filters->previousPeriodEnd())
            ->count();

        return [
            'users' => DB::table('users')->whereNull('deleted_at')->count(),
            'tools' => DB::table('dc_projects')->whereNull('deleted_at')->count(),
            'forms' => DB::table('dc_forms')->whereNull('deleted_at')->count(),
            'submissions' => $current,
            'submissions_delta' => $this->kpiDelta($current, $previous),
            'submissions_today' => DcSubmission::query()->whereDate('received_at', today())->count(),
            'by_status' => (clone $query)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'by_channel' => (clone $query)
                ->select('channel', DB::raw('count(*) as total'))
                ->groupBy('channel')
                ->pluck('total', 'channel'),
            'last_updated' => DcSubmission::query()->whereNull('deleted_at')->max('received_at'),
        ];
    }

    /** @return array<int, array{date: string, count: int}> */
    public function platformSubmissionsTrend(int $days): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = DcSubmission::query()
            ->whereNull('deleted_at')
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

    /** @return list<array{tool: DcProject, count: int}> */
    public function toolsBySubmissionVolume(int $limit, AccFilters $filters): array
    {
        $rows = DcSubmission::query()
            ->whereNull('deleted_at')
            ->where('received_at', '>=', $filters->periodStart())
            ->where('received_at', '<=', $filters->periodEnd())
            ->select('project_id', DB::raw('count(*) as total'))
            ->groupBy('project_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $tools = DcProject::query()->whereIn('id', $rows->pluck('project_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => [
            'tool' => $tools[$row->project_id],
            'count' => (int) $row->total,
        ])->filter(fn ($r) => $r['tool'] !== null)->values()->all();
    }

    /** @return array<string, mixed> */
    public function platformDataFreshness(): array
    {
        return [
            'database' => [
                'label' => 'Platform database',
                'status' => 'connected',
                'updated_at' => DcSubmission::query()->whereNull('deleted_at')->max('received_at'),
            ],
            'ingestion' => [
                'label' => 'Submission ingest',
                'status' => 'active',
                'updated_at' => now(),
            ],
        ];
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

    /**
     * Live tercile tally from indicator log submissions (protocol-eligible rows only).
     *
     * @return array<string, mixed>|null
     */
    public function tercileWeightingDashboard(DcProject $project, ?string $site = null, ?string $season = null, ?AccFilters $filters = null): ?array
    {
        $query = DcSubmission::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['rejected', 'draft']);

        if ($filters) {
            $query->where('received_at', '>=', $filters->periodStart())
                ->where('received_at', '<=', $filters->periodEnd());
        }

        $rows = $query->get(['data']);
        $site = $site ?? $filters?->site;
        $season = $season ?? $filters?->season;

        $eligible = [];
        foreach ($rows as $row) {
            $data = is_array($row->data) ? $row->data : [];
            if ($site && ($data['record_community_site'] ?? null) !== $site) {
                continue;
            }
            if ($season && ($data['record_season_year'] ?? null) !== $season) {
                continue;
            }
            if (! $this->isTercileEligible($data)) {
                continue;
            }
            $eligible[] = $data;
        }

        if ($eligible === []) {
            return null;
        }

        $counts = ['3' => 0, '2' => 0, '1' => 0];
        $weightSums = ['3' => 0.0, '2' => 0.0, '1' => 0.0];
        $totalWeight = 0.0;

        foreach ($eligible as $data) {
            $t = (string) ($data['season_tercile_prediction'] ?? '');
            if (! isset($counts[$t])) {
                continue;
            }
            $counts[$t]++;
            $w = (float) ($data['final_weight'] ?? 1);
            if ($w <= 0) {
                $w = 1.0;
            }
            $weightSums[$t] += $w;
            $totalWeight += $w;
        }

        $n = count($eligible);
        $pct = fn (int $c): float => $n > 0 ? round(100 * $c / $n, 1) : 0.0;
        $wpct = fn (float $s): float => $totalWeight > 0 ? round(100 * $s / $totalWeight, 1) : 0.0;

        return [
            'eligible_count' => $n,
            'simple' => [
                'above' => ['count' => $counts['3'], 'pct' => $pct($counts['3'])],
                'normal' => ['count' => $counts['2'], 'pct' => $pct($counts['2'])],
                'below' => ['count' => $counts['1'], 'pct' => $pct($counts['1'])],
            ],
            'weighted' => [
                'above' => ['sum' => $weightSums['3'], 'pct' => $wpct($weightSums['3'])],
                'normal' => ['sum' => $weightSums['2'], 'pct' => $wpct($weightSums['2'])],
                'below' => ['sum' => $weightSums['1'], 'pct' => $wpct($weightSums['1'])],
            ],
        ];
    }

    /** @return list<array{indicator_id: string, submissions: int, hit_rate: ?float, reliability_drift: ?float}> */
    public function calibrationByIndicator(DcProject $project, int $limit = 25): array
    {
        $grouped = [];

        DcSubmission::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['rejected', 'draft'])
            ->orderBy('id')
            ->get(['data'])
            ->each(function (DcSubmission $row) use (&$grouped): void {
                $data = is_array($row->data) ? $row->data : [];
                $id = $data['unique_indicator_id'] ?? null;
                if (! $id) {
                    return;
                }
                $grouped[$id] ??= ['hits' => 0, 'total' => 0, 'hist' => [], 'curr' => []];
                $grouped[$id]['total']++;
                $outcome = $data['verification_outcome'] ?? $data['actual_season_classification'] ?? null;
                if (in_array((string) $outcome, ['2', 'hit', 'yes'], true)) {
                    $grouped[$id]['hits']++;
                }
                if (isset($data['historical_reliability_rating'])) {
                    $grouped[$id]['hist'][] = (float) $data['historical_reliability_rating'];
                }
                if (isset($data['current_reliability_rating'])) {
                    $grouped[$id]['curr'][] = (float) $data['current_reliability_rating'];
                }
            });

        $out = [];
        foreach ($grouped as $indicatorId => $stats) {
            $avg = fn (array $xs): ?float => $xs === [] ? null : round(array_sum($xs) / count($xs), 2);
            $out[] = [
                'indicator_id' => $indicatorId,
                'submissions' => $stats['total'],
                'hit_rate' => $stats['total'] > 0 ? round($stats['hits'] / $stats['total'], 2) : null,
                'reliability_drift' => ($avg($stats['hist']) !== null && $avg($stats['curr']) !== null)
                    ? round($avg($stats['curr']) - $avg($stats['hist']), 2)
                    : null,
            ];
        }

        usort($out, fn ($a, $b) => $b['submissions'] <=> $a['submissions']);

        return array_slice($out, 0, $limit);
    }

    /** @param array<string, mixed> $data */
    private function isTercileEligible(array $data): bool
    {
        if (($data['sighted_this_season'] ?? null) !== '1') {
            return false;
        }
        if (empty($data['confirmed_by']) || empty($data['approved_by'])) {
            return false;
        }
        if (($data['validation_status'] ?? null) === '4') {
            return false;
        }
        $t = (string) ($data['season_tercile_prediction'] ?? '0');

        return in_array($t, ['1', '2', '3'], true);
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
