<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Models\DcSubmission;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        $user = $request->user();

        $toolIds = $user->isSuperAdmin()
            ? DcProject::query()->pluck('id')
            : $user->toolMemberships()->pluck('project_id');

        $tools = DcProject::query()
            ->whereIn('id', $toolIds)
            ->withCount('submissions')
            ->latest('updated_at')
            ->limit(12)
            ->get();

        $recentSubmissions = DcSubmission::query()
            ->whereIn('project_id', $toolIds)
            ->with(['form', 'project'])
            ->latest('received_at')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'tools' => $tools,
            'recentSubmissions' => $recentSubmissions,
            'platformStats' => $user->isSuperAdmin() ? $analytics->platformStats() : null,
        ]);
    }
}
