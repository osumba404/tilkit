<?php

namespace App\Http\Controllers;

use App\Enums\DataSubjectRequestType;
use App\Models\DataSubjectRequest;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Support\Tilkit\WeatherTool;
use App\Services\KenyaDpa\TilkitPersonalDataService;
use App\Services\ToolAccessService;
use App\Services\ToolAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ToolDataProtectionController extends Controller
{
    public function index(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanManage($request->user(), $tool);

        $requests = DataSubjectRequest::query()
            ->where('project_id', $tool->id)
            ->latest()
            ->limit(50)
            ->get();

        $tool->load('owner');
        $inventory = WeatherTool::isWeatherModule($tool)
            ? config('kenya_dpa.tilkit_personal_data_columns', [])
            : [];

        return view('tools.data-protection', [
            'tool' => $tool,
            'requests' => $requests,
            'lawfulBases' => config('kenya_dpa.lawful_bases', []),
            'inventory' => $inventory,
            'types' => DataSubjectRequestType::cases(),
            'governanceUpdateRoute' => $tool->module === 'tilkit_weather'
                ? 'tools.tilkit.data-protection.update'
                : 'tools.data-protection.update',
            'requestResolveRoute' => $tool->module === 'tilkit_weather'
                ? 'tools.tilkit.data-protection.requests.resolve'
                : 'tools.data-protection.requests.resolve',
            'tilkitNav' => $tool->module === 'tilkit_weather' ? 'team' : null,
        ]);
    }

    public function updateGovernance(
        Request $request,
        DcProject $tool,
        ToolAccessService $access,
        ToolAuditService $audit,
    ): RedirectResponse {
        $access->assertCanManage($request->user(), $tool);

        $validated = $request->validate([
            'purpose' => ['required', 'string', 'max:5000'],
            'lawful_basis' => ['required', Rule::in(array_keys(config('kenya_dpa.lawful_bases', [])))],
            'retention_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'retention_policy_summary' => ['nullable', 'string', 'max:5000'],
            'data_controller_name' => ['nullable', 'string', 'max:200'],
            'data_controller_email' => ['nullable', 'email', 'max:190'],
            'data_protection_contact_email' => ['nullable', 'email', 'max:190'],
            'odpc_registration_number' => ['nullable', 'string', 'max:80'],
            'privacy_policy_url' => ['nullable', 'url', 'max:500'],
            'personal_data_notice' => ['nullable', 'string', 'max:20000'],
            'cross_border_transfers_allowed' => ['sometimes', 'boolean'],
            'cross_border_safeguards' => ['nullable', 'string', 'max:5000'],
        ]);

        $tool->update([
            ...$validated,
            'cross_border_transfers_allowed' => $request->boolean('cross_border_transfers_allowed'),
        ]);

        $audit->log($tool, $request->user(), 'tool.dpa_governance_updated', 'dc_project', $tool->id);

        return back()->with('status', 'Data protection settings saved.');
    }

    public function resolveRequest(
        Request $request,
        DcProject $tool,
        DataSubjectRequest $dataSubjectRequest,
        ToolAccessService $access,
        TilkitPersonalDataService $personalData,
        ToolAuditService $audit,
    ): RedirectResponse {
        $access->assertCanManage($request->user(), $tool);
        abort_unless($dataSubjectRequest->project_id === $tool->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,rejected'],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
            'anonymize_record_uid' => ['nullable', 'string', 'max:80'],
        ]);

        $recordUid = $validated['anonymize_record_uid'] ?? $dataSubjectRequest->record_reference;
        if ($validated['status'] === 'completed' && filled($recordUid) && WeatherTool::isWeatherModule($tool)) {
            $record = $this->findTilkitRecordByUid($tool, (string) $recordUid);
            if ($record !== null) {
                if ($dataSubjectRequest->request_type === DataSubjectRequestType::Erasure) {
                    $personalData->anonymizeRecord($record);
                } elseif ($dataSubjectRequest->request_type === DataSubjectRequestType::Objection) {
                    $record->update(['processing_restricted' => true]);
                }
            }
        }

        $dataSubjectRequest->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);

        $audit->log($tool, $request->user(), 'data_subject_request.resolved', 'data_subject_request', $dataSubjectRequest->id);

        return back()->with('status', 'Data subject request updated.');
    }

    private function findTilkitRecordByUid(DcProject $tool, string $indicatorUid): ?TilkitIndicatorRecord
    {
        return TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id)->where('indicator_uid', $indicatorUid))
            ->first();
    }
}
