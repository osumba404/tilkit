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

        $submissions = DcSubmission::query()
            ->where('project_id', $tool->id)
            ->with(['form', 'collector'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('form_id'), fn ($q) => $q->where('form_id', $request->integer('form_id')))
            ->latest('received_at')
            ->paginate(25)
            ->withQueryString();

        $forms = $tool->forms()->orderBy('title')->get(['id', 'title']);

        return view('tools.submissions.index', compact('tool', 'submissions', 'forms'));
    }

    public function show(Request $request, DcProject $tool, DcSubmission $submission, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);
        abort_unless($submission->project_id === $tool->id, 404);

        $submission->load(['form', 'formVersion.fields', 'answers.field', 'collector']);

        return view('tools.submissions.show', compact('tool', 'submission'));
    }
}
