<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitWorkbookMedia;
use App\Services\Tilkit\SeasonOutcomeScoringService;
use App\Services\Tilkit\TilkitPersonaService;
use App\Services\Tilkit\TilkitSignOffGuard;
use App\Services\Tilkit\WorkbookRecordService;
use App\Services\ToolAccessService;
use App\Support\Tilkit\CommunityDataListRegistry;
use App\Support\Tilkit\WorkbookColumnRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolTilkitWeatherController extends Controller
{
    public function overview(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        $tool->loadCount(['tilkitSites', 'tilkitSeasons', 'tilkitIndicators']);
        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['indicator', 'site', 'season'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $protocol = config('tilkit.weather.protocol_version');
        $rawFieldCount = count(WorkbookColumnRegistry::rawColumns());

        return view('tools.tilkit.overview', compact('tool', 'protocol', 'records', 'rawFieldCount'));
    }

    public function createRecord(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        return view('tools.tilkit.record-form', [
            'tool' => $tool,
            'record' => null,
            'sites' => TilkitSite::query()->where('project_id', $tool->id)->orderBy('name')->get(),
            'seasons' => TilkitSeason::query()->where('project_id', $tool->id)->orderBy('label')->get(),
            'indicators' => TilkitIndicator::query()->where('project_id', $tool->id)->orderBy('indicator_uid')->get(),
            'stageGroups' => WorkbookColumnRegistry::rawColumnsByStage(),
            'workbookValues' => [],
            'derivedMap' => [],
            'derivedColumns' => WorkbookColumnRegistry::derivedColumns(),
        ]);
    }

    public function storeRecord(Request $request, DcProject $tool, ToolAccessService $access, WorkbookRecordService $workbook): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);

        $validated = $request->validate([
            'indicator_id' => ['required', 'exists:tilkit_indicators,id'],
            'site_id' => ['required', 'exists:tilkit_sites,id'],
            'season_id' => ['required', 'exists:tilkit_seasons,id'],
            'workbook' => ['nullable', 'array'],
            'workbook.*' => ['nullable'],
            'workbook_photos' => ['nullable', 'array'],
            'workbook_photos.*' => ['file', 'image', 'max:8192'],
        ]);

        $this->assertToolOwnsReferences($tool, $validated);

        $record = TilkitIndicatorRecord::query()->create([
            'indicator_id' => $validated['indicator_id'],
            'site_id' => $validated['site_id'],
            'season_id' => $validated['season_id'],
            'entered_by' => $request->user()->id,
        ]);

        $workbook->storeRawValues($record, $validated['workbook'] ?? []);
        $workbook->storeReferenceColumns($record->fresh());
        $this->storePhotos($request, $record);

        return redirect()
            ->route('tools.tilkit.records.wizard', [$tool, $record, 'A'])
            ->with('status', 'Indicator record created — continue in the Community Data List wizard.');
    }

    public function editRecord(Request $request, DcProject $tool, TilkitIndicatorRecord $record, ToolAccessService $access, WorkbookRecordService $workbook): View
    {
        $access->assertCanView($request->user(), $tool);
        $this->assertRecordBelongsToTool($tool, $record);

        $record->load(['indicator', 'site', 'season', 'workbookMedia', 'workbookValues', 'sightings']);

        $derivedMap = [];
        foreach ($record->workbookValues->where('is_derived', true) as $cell) {
            $meta = WorkbookColumnRegistry::forColumn($cell->column_letter);
            $kind = $meta ? WorkbookColumnRegistry::inputKind($meta) : 'text';
            $derivedMap[$cell->column_letter] = [
                'field' => $meta['field'] ?? $cell->field_key,
                'value' => \App\Support\Tilkit\WorkbookValueNormalizer::display(
                    $cell->value_number !== null ? (float) $cell->value_number : null,
                    $cell->value_text,
                    $cell->value_date?->format('Y-m-d'),
                    $cell->value_json,
                    $kind,
                ),
            ];
        }

        return view('tools.tilkit.record-form', [
            'tool' => $tool,
            'record' => $record,
            'sites' => TilkitSite::query()->where('project_id', $tool->id)->orderBy('name')->get(),
            'seasons' => TilkitSeason::query()->where('project_id', $tool->id)->orderBy('label')->get(),
            'indicators' => TilkitIndicator::query()->where('project_id', $tool->id)->orderBy('indicator_uid')->get(),
            'stageGroups' => WorkbookColumnRegistry::rawColumnsByStage(),
            'workbookValues' => $workbook->rawWorkbookMap($record),
            'derivedMap' => $derivedMap,
            'derivedColumns' => WorkbookColumnRegistry::derivedColumns(),
        ]);
    }

    public function updateRecord(Request $request, DcProject $tool, TilkitIndicatorRecord $record, ToolAccessService $access, WorkbookRecordService $workbook, TilkitPersonaService $personas): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $this->assertRecordBelongsToTool($tool, $record);

        $validated = $request->validate([
            'workbook' => ['nullable', 'array'],
            'workbook.*' => ['nullable'],
            'workbook_photos' => ['nullable', 'array'],
            'workbook_photos.*' => ['file', 'image', 'max:8192'],
        ]);

        $raw = $validated['workbook'] ?? [];
        $this->assertPersonaForWorkbookFields($personas, $request->user(), $tool, $raw);
        $raw = app(TilkitSignOffGuard::class)->applySignOff($record, $request->user(), $raw);
        $workbook->storeRawValues($record, $raw);
        $workbook->storeReferenceColumns($record->fresh());
        $this->storePhotos($request, $record);

        if ($request->boolean('score_outcome')) {
            app(SeasonOutcomeScoringService::class)->persistOutcome($record->fresh());
        }

        return redirect()
            ->route('tools.tilkit.records.edit', [$tool, $record])
            ->with('status', 'Workbook fields updated.');
    }

    public function wizard(Request $request, DcProject $tool, TilkitIndicatorRecord $record, string $section, ToolAccessService $access, WorkbookRecordService $workbook): View
    {
        $access->assertCanView($request->user(), $tool);
        $this->assertRecordBelongsToTool($tool, $record);
        $section = strtoupper($section);
        abort_unless(preg_match('/^[A-E]$/', $section), 404);

        $record->load(['indicator', 'site', 'season', 'sightings', 'workbookMedia']);
        $letters = CommunityDataListRegistry::columnLettersForSectionKey($section);
        $columns = array_values(array_filter(
            WorkbookColumnRegistry::rawColumns(),
            fn (array $col) => in_array($col['column'], $letters, true)
        ));
        $checklist = CommunityDataListRegistry::itemsForSectionKey($section);
        $workbookValues = $workbook->rawWorkbookMap($record);

        return view('tools.tilkit.record-wizard', [
            'tool' => $tool,
            'record' => $record,
            'section' => $section,
            'checklist' => $checklist,
            'columns' => $columns,
            'workbookValues' => $workbookValues,
            'sections' => ['A', 'B', 'C', 'D', 'E'],
        ]);
    }

    public function updateWizard(Request $request, DcProject $tool, TilkitIndicatorRecord $record, string $section, ToolAccessService $access, WorkbookRecordService $workbook, TilkitPersonaService $personas): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $this->assertRecordBelongsToTool($tool, $record);
        $section = strtoupper($section);

        $validated = $request->validate([
            'workbook' => ['nullable', 'array'],
            'workbook.*' => ['nullable'],
        ]);
        $raw = $validated['workbook'] ?? [];
        $this->assertPersonaForWorkbookFields($personas, $request->user(), $tool, $raw);
        $raw = app(TilkitSignOffGuard::class)->applySignOff($record, $request->user(), $raw);
        $workbook->storeRawValues($record, $raw);
        $workbook->storeReferenceColumns($record->fresh());

        $next = $request->input('next_section');
        if (is_string($next) && preg_match('/^[A-E]$/', strtoupper($next))) {
            return redirect()->route('tools.tilkit.records.wizard', [$tool, $record, strtoupper($next)])
                ->with('status', "Section {$section} saved.");
        }

        return redirect()->route('tools.tilkit.records.edit', [$tool, $record])
            ->with('status', 'Workshop sections saved.');
    }

    /** @param  array<string, mixed>  $validated */
    private function assertToolOwnsReferences(DcProject $tool, array $validated): void
    {
        abort_unless(
            TilkitIndicator::query()->where('project_id', $tool->id)->where('id', $validated['indicator_id'])->exists(),
            422
        );
        abort_unless(
            TilkitSite::query()->where('project_id', $tool->id)->where('id', $validated['site_id'])->exists(),
            422
        );
        abort_unless(
            TilkitSeason::query()->where('project_id', $tool->id)->where('id', $validated['season_id'])->exists(),
            422
        );
    }

    private function assertRecordBelongsToTool(DcProject $tool, TilkitIndicatorRecord $record): void
    {
        abort_unless(
            $record->indicator && $record->indicator->project_id === $tool->id,
            404
        );
    }

    /** @param  array<string, mixed>  $workbook */
    private function assertPersonaForWorkbookFields(TilkitPersonaService $personas, \App\Models\User $user, DcProject $tool, array $workbook): void
    {
        $persona = $personas->personaFor($user, $tool);
        if (array_key_exists('AC', $workbook) && ! $persona->canConfirm()) {
            abort(403, 'Your role cannot set Confirmed (AC).');
        }
        if (array_key_exists('AD', $workbook) && ! $persona->canApprove()) {
            abort(403, 'Your role cannot set Approved (AD).');
        }
        if (array_key_exists('AE', $workbook) && ! $persona->canVerify()) {
            abort(403, 'Your role cannot set Verified (AE).');
        }
    }

    private function storePhotos(Request $request, TilkitIndicatorRecord $record): void
    {
        if (! $request->hasFile('workbook_photos')) {
            return;
        }

        $sort = (int) $record->workbookMedia()->max('sort_order');
        foreach ($request->file('workbook_photos') as $file) {
            $sort++;
            $path = $file->store("tilkit/records/{$record->id}", 'local');
            TilkitWorkbookMedia::query()->create([
                'indicator_record_id' => $record->id,
                'column_letter' => 'Y',
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'sort_order' => $sort,
            ]);
        }

        app(WorkbookRecordService::class)->storeRawValues($record, [
            'Y' => $record->workbookMedia()->count(),
        ]);
    }
}
