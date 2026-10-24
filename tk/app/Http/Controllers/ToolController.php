<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\ToolAccessService;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (WeatherTool::singleToolMode()) {
            $user = $request->user();
            $tool = WeatherTool::ensurePlatformTool($user);
            WeatherTool::ensureMember($user);

            return redirect()->route('tools.tilkit.overview', $tool);
        }

        $user = $request->user();
        $query = DcProject::query()->withCount(['forms', 'submissions']);

        if (! $user->isSuperAdmin()) {
            $query->whereIn('id', $user->toolMemberships()->pluck('project_id'));
        }

        $tools = $query->latest('created_at')->paginate(15);

        return view('tools.index', compact('tools'));
    }

    public function create(): View|RedirectResponse
    {
        if (WeatherTool::singleToolMode()) {
            abort(404);
        }

        return view('tools.create');
    }

    public function store(Request $request, ToolAccessService $access): RedirectResponse
    {
        if (WeatherTool::singleToolMode()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'purpose' => ['required', 'string', 'max:5000'],
            'lawful_basis' => ['required', 'in:consent,contract,legal_obligation,public_interest,legitimate_interest'],
            'module' => ['nullable', 'string', 'max:40'],
        ]);

        $tool = $access->createTool($request->user(), $validated);

        return redirect()
            ->route('tools.show', $tool)
            ->with('status', 'Tool created. You are its administrator.');
    }

    public function show(Request $request, DcProject $tool, ToolAccessService $access): View|RedirectResponse
    {
        if (WeatherTool::singleToolMode() && WeatherTool::isWeatherModule($tool)) {
            return redirect()->route('tools.tilkit.overview', $tool);
        }

        $access->assertCanView($request->user(), $tool);
        $tool->load([
            'forms' => fn ($q) => $q->latest(),
            'owner',
            'members.user',
            'invitations' => fn ($q) => $q->whereNull('accepted_at')->where('expires_at', '>', now()),
        ]);
        $capabilities = $request->user()->toolCapabilitiesFor($tool);
        $auditLogs = $capabilities->canManageTool()
            ? $tool->auditLogs()->with('user')->latest('created_at')->limit(15)->get()
            : collect();

        return view('tools.show', compact('tool', 'capabilities', 'auditLogs'));
    }
}
