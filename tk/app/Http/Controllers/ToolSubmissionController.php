<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Models\DcSubmission;
use App\Services\ToolAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolSubmissionController extends Controller
{
    public function index(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);
        $role = $request->user()->toolRoleFor($tool);

        $query = DcSubmission::query()
            ->where('project_id', $tool->id)
            ->with(['form', 'collector']);

        $access->scopeSubmissionsForUser($request->user(), $tool, $query);

        $submissions = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('form_id'), fn ($q) => $q->where('form_id', $request->integer('form_id')))
            ->latest('received_at')
            ->paginate(25)
            ->withQueryString();

        $forms = $tool->forms()->orderBy('title')->get(['id', 'title']);

        return view('tools.submissions.index', compact('tool', 'submissions', 'forms', 'role'));
    }

    public function show(Request $request, DcProject $tool, DcSubmission $submission, ToolAccessService $access): View
    {
        $access->assertCanViewSubmission($request->user(), $tool, $submission);

        $submission->load(['form', 'formVersion.fields', 'answers.field', 'collector']);

        return view('tools.submissions.show', compact('tool', 'submission'));
    }
}
