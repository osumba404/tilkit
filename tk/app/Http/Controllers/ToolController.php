<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\ToolAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = DcProject::query()->withCount(['forms', 'submissions']);

        if (! $user->isSuperAdmin()) {
            $query->whereIn('id', $user->toolMemberships()->pluck('project_id'));
        }

        $tools = $query->latest('created_at')->paginate(15);

        return view('tools.index', compact('tools'));
    }

    public function create(): View
    {
        return view('tools.create');
    }

    public function store(Request $request, ToolAccessService $access): RedirectResponse
    {
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

    public function show(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);
        $tool->load(['forms' => fn ($q) => $q->latest(), 'owner']);
        $role = $request->user()->toolRoleFor($tool);

        return view('tools.show', compact('tool', 'role'));
    }
}
