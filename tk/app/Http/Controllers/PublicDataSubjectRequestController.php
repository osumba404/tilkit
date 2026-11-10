<?php

namespace App\Http\Controllers;

use App\Enums\DataSubjectRequestType;
use App\Models\DataSubjectRequest;
use App\Models\DcProject;
use App\Services\ToolAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicDataSubjectRequestController extends Controller
{
    public function create(DcProject $tool): View
    {
        app()->setLocale($tool->default_locale ?: 'en');

        return view('public.data-subject-request', [
            'tool' => $tool,
            'types' => DataSubjectRequestType::cases(),
        ]);
    }

    public function store(Request $request, DcProject $tool, ToolAuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'request_type' => ['required', Rule::enum(DataSubjectRequestType::class)],
            'requester_name' => ['required', 'string', 'max:200'],
            'requester_email' => ['required', 'email', 'max:190'],
            'requester_phone' => ['nullable', 'string', 'max:40'],
            'record_reference' => ['nullable', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:5000'],
        ]);

        $dsr = DataSubjectRequest::query()->create([
            'project_id' => $tool->id,
            ...$validated,
            'status' => 'pending',
        ]);

        $audit->log($tool, null, 'data_subject_request.received', 'data_subject_request', $dsr->id, [
            'type' => $validated['request_type'],
            'email' => $validated['requester_email'],
        ]);

        return redirect()
            ->route('public.tools.privacy', $tool)
            ->with('status', 'Your request was received. The data controller will respond within 30 days.');
    }
}
