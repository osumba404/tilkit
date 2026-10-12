<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Support\AccFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAnalyticsController extends Controller
{
    public function overview(Request $request, AnalyticsService $analytics): View
    {
        $filters = AccFilters::fromRequest($request);
        $overview = $analytics->platformOverview($filters);
        $trend = $analytics->platformSubmissionsTrend($filters->periodDays);
        $topTools = $analytics->toolsBySubmissionVolume(10, $filters);
        $freshness = $analytics->platformDataFreshness();

        return view('platform.analytics.overview', compact('filters', 'overview', 'trend', 'topTools', 'freshness'));
    }

    public function sources(AnalyticsService $analytics): View
    {
        $freshness = $analytics->platformDataFreshness();

        return view('platform.analytics.sources', compact('freshness'));
    }


}
