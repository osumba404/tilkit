<?php

namespace App\Services;

use App\Models\DcProject;
use App\Models\DcSubmission;

class DuplicateDetectionService
{
    /**
     * @return list<string> warning messages
     */
    public function checkIndicatorLog(DcProject $project, array $payload): array
    {
        $indicatorId = $payload['unique_indicator_id'] ?? null;
        $site = $payload['record_community_site'] ?? $payload['community_site_name'] ?? null;
        $season = $payload['record_season_year'] ?? $payload['season_year'] ?? null;

        if (! $indicatorId || ! $site || ! $season) {
            return [];
        }

        $exists = DcSubmission::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['rejected', 'draft'])
            ->get(['data'])
            ->contains(function (DcSubmission $row) use ($indicatorId, $site, $season) {
                $data = is_array($row->data) ? $row->data : [];

                return ($data['unique_indicator_id'] ?? null) === $indicatorId
                    && in_array($data['record_community_site'] ?? $data['community_site_name'] ?? null, [$site], true)
                    && in_array($data['record_season_year'] ?? $data['season_year'] ?? null, [$season], true);
            });

        if ($exists) {
            return [
                'A submission already exists for indicator '.$indicatorId.' at '.$site.' for '.$season.'. You can still submit if this is intentional.',
            ];
        }

        return [];
    }
}
