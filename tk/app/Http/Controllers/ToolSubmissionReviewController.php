<?php

namespace App\Http\Controllers;

use App\Mail\SubmissionReviewStatusMail;
use App\Models\DcProject;
use App\Models\DcSubmission;
use App\Services\ToolAccessService;
use App\Services\ToolAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ToolSubmissionReviewController extends Controller
{
    private const STATUSES = ['submitted', 'in_review', 'approved', 'rejected'];

    public function queue(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $capabilities = $request->user()->toolCapabilitiesFor($tool);
        abort_unless($capabilities->canReviewSubmissions(), 403);

        $submissions = DcSubmission::query()
            ->where('project_id', $tool->id)
            ->whereIn('status', ['submitted', 'in_review'])
            ->with(['form', 'collector'])
            ->latest('received_at')
            ->paginate(25);

        return view('tools.submissions.review-queue', compact('tool', 'submissions', 'capabilities'));
    }

    public function update(
        Request $request,
        DcProject $tool,
        DcSubmission $submission,
        ToolAccessService $access,
        ToolAuditService $audit,
    ): RedirectResponse {
        $capabilities = $request->user()->toolCapabilitiesFor($tool);
        abort_unless($capabilities->canReviewSubmissions(), 403);
        abort_unless($submission->project_id === $tool->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $previous = $submission->status;

        $submission->update([
            'status' => $validated['status'],
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $audit->log($tool, $request->user(), 'submission.review_updated', 'dc_submission', $submission->id, [
            'from' => $previous,
            'to' => $validated['status'],
        ]);

        if (
            in_array($validated['status'], ['approved', 'rejected'], true)
            && $submission->collector?->email
            && $previous !== $validated['status']
        ) {
            Mail::to($submission->collector->email)->send(
                new SubmissionReviewStatusMail($submission->fresh(['form', 'project']), $validated['status']),
            );
        }

        return back()->with('status', 'Submission marked as '.$validated['status'].'.');
    }
}
