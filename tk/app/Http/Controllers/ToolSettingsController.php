<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Models\ToolScheduledExport;
use App\Services\ToolAccessService;
use App\Services\ToolAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolSettingsController extends Controller
{
    public function edit(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanManage($request->user(), $tool);

        $scheduledExport = $tool->scheduledExports()->first();

        return view('tools.settings', compact('tool', 'scheduledExport'));
    }

    public function update(
        Request $request,
        DcProject $tool,
        ToolAccessService $access,
        ToolAuditService $audit,
    ): RedirectResponse {
        $access->assertCanManage($request->user(), $tool);

        $validated = $request->validate([
            'consent_text' => ['nullable', 'string', 'max:20000'],
            'consent_ack_label' => ['nullable', 'string', 'max:500'],
            'consent_require_signature' => ['sometimes', 'boolean'],
            'brand_footer' => ['nullable', 'string', 'max:1000'],
            'default_locale' => ['nullable', 'string', 'max:10', 'in:en,fr,tw'],
            'export_recipients' => ['nullable', 'string', 'max:1000'],
            'export_frequency' => ['nullable', 'in:daily,weekly,monthly'],
        ]);

        $validated['consent_require_signature'] = $request->boolean('consent_require_signature');

        $tool->update(collect($validated)->only([
            'consent_text',
            'consent_ack_label',
            'consent_require_signature',
            'brand_footer',
            'default_locale',
        ])->all());

        if ($request->filled('export_recipients')) {
            ToolScheduledExport::updateOrCreate(
                ['project_id' => $tool->id],
                [
                    'recipient_emails' => $validated['export_recipients'],
                    'frequency' => $validated['export_frequency'] ?? 'weekly',
                    'is_active' => true,
                ],
            );
        }

        $audit->log($tool, $request->user(), 'tool.settings_updated', 'dc_project', $tool->id);

        return back()->with('status', 'Tool settings saved.');
    }
}
