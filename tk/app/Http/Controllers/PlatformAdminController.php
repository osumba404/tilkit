<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Models\DcProject;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAdminController extends Controller
{
    public function index(AnalyticsService $analytics): View
    {
        $stats = $analytics->platformStats();

        $recentTools = DcProject::query()
            ->with('owner')
            ->latest('created_at')
            ->limit(10)
            ->get();

        $users = User::query()
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->limit(20)
            ->get(['id', 'name', 'email', 'platform_role', 'status', 'created_at']);

        return view('platform.index', compact('stats', 'recentTools', 'users'));
    }

    public function promoteSuperAdmin(Request $request, User $user): RedirectResponse
    {
        $user->update(['platform_role' => PlatformRole::SuperAdmin]);

        return back()->with('status', "{$user->name} is now a super admin.");
    }
}
