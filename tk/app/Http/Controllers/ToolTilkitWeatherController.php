<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Services\Tilkit\SiteIndicatorCatalogService;
use App\Models\Tilkit\TilkitWorkbookMedia;
use App\Services\Tilkit\SeasonOutcomeScoringService;
use App\Services\Tilkit\TilkitInsightsService;
use App\Services\Tilkit\TilkitProgramContext;
use App\Services\Tilkit\TilkitPersonaService;
use App\Services\Tilkit\TilkitSignOffGuard;
use App\Services\Tilkit\WorkbookRecordService;
use App\Services\ToolAccessService;
use App\Support\Tilkit\CommunityDataListRegistry;
use App\Support\Tilkit\IndicatorLegendTree;
use App\Support\Tilkit\WorkbookCapturePreparer;
use App\Support\Tilkit\WorkbookColumnRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolTilkitWeatherController extends Controller
{
    public function __construct(
        private TilkitProgramContext $programs,
    ) {}

    public function overview(Request $request, DcProject $tool, ToolAccessService $access, TilkitInsightsService $insights): View
    {
        $access->assertCanView($request->user(), $tool);

        $tool->loadCount(['tilkitSites', 'tilkitSeasons', 'tilkitIndicators']);
        $summary = $insights->summary($tool);
        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['indicator', 'site', 'season'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $rawFieldCount = count(WorkbookColumnRegistry::rawColumns());

        return view('tools.tilkit.overview', [
            'tool' => $tool,
            'records' => $records,
            'summary' => $summary,
            'mapPoints' => $insights->mapPointsForTool($tool),
            'rawFieldCount' => $rawFieldCount,
            'tilkitNav' => 'overview',
        ]);
    }

    public function createRecord(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        if ($request->filled('program_id')) {
            $this->programs->setActive($tool, $request->integer('program_id'));
        }

        $indicators = TilkitIndicator::query()
            ->where('project_id', $tool->id)
            ->with('code')
            ->orderBy('indicator_uid')
            ->get();

        return view('tools.tilkit.record-form', [
            'tool' => $tool,
            'record' => null,
            'activeProgram' => $this->programs->active($tool),
            'sites' => $this->programs->sitesQuery($tool)->get(),
            'seasons' => $this->programs->seasonsQuery($tool)->get(),
            'indicators' => $indicators,
            'indicatorOptionsUrl' => route('tools.tilkit.capture.indicator-options', $tool),
            'letterBands' => WorkbookColumnRegistry::rawColumnBandsForForm(),
            'stageGroups' => WorkbookColumnRegistry::rawColumnsByStage(),
            'workbookValues' => [],
            'derivedMap' => [],
            'derivedColumns' => WorkbookColumnRegistry::derivedColumns(),
            'tilkitNav' => 'capture',
        ]);
    }

    public function captureIndicatorOptions(
        Request $request,
        DcProject $tool,
        ToolAccessService $access,
        SiteIndicatorCatalogService $catalog,
        IndicatorLegendTree $legendTree,
    ): JsonResponse {
        $access->assertCanView($request->user(), $tool);

        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:tilkit_sites,id'],
            'season_id' => ['required', 'integer', 'exists:tilkit_seasons,id'],
            'collection_date' => ['required', 'date'],
        ]);

        $site = TilkitSite::query()->where('project_id', $tool->id)->where('id', $validated['site_id'])->firstOrFail();
        $season = TilkitSeason::query()->where('project_id', $tool->id)->where('id', $validated['season_id'])->firstOrFail();

        abort_unless(
            $site->program_id !== null
            && $season->program_id !== null
            && (int) $site->program_id === (int) $season->program_id,
            422,
            'Site and season must belong to the same program.',
        );
        if ($season->site_id !== null) {
            abort_unless((int) $season->site_id === (int) $site->id, 422, 'Season must belong to the selected site.');
        }

        $collectionMonth = $catalog->collectionMonthFromDate($validated['collection_date']);
        $allowedIds = $catalog->allowedCodeIdsForCapture($site, $season, $collectionMonth);

        $indicators = TilkitIndicator::query()
            ->where('project_id', $tool->id)
            ->whereIn('indicator_code_id', $allowedIds)
            ->with('code')
            ->orderBy('indicator_uid')
            ->get()
            ->map(fn (TilkitIndicator $i) => [
                'id' => $i->id,
                'indicator_code_id' => (int) $i->indicator_code_id,
                'indicator_uid' => $i->indicator_uid,
                'local_name' => $i->local_name,
                'workbook' => $i->code
                    ? $legendTree->workbookDescriptor($i->code, $i->local_name)
                    : ['A' => '', 'B' => '', 'C' => (string) $i->local_name, 'D' => ''],
            ]);

        return response()->json([
            'collection_month' => $collectionMonth,
            'allowed_code_ids' => $allowedIds,
            'season_union_code_ids' => $catalog->codeIdsForSeasonUnion($site, $season),
            'legend' => $legendTree->build($allowedIds),
            'indicators' => $indicators,
            'messages' => $allowedIds === []
                ? ['No indicator codes are assigned to this site for the collection month, or the date falls outside the selected season.']
                : [],
        ]);
    }

    public function storeRecord(Request $request, DcProject $tool, ToolAccessService $access, WorkbookRecordService $workbook): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);

        if ($request->filled('program_id')) {
            $this->programs->setActive($tool, $request->integer('program_id'));
        }

        $validated = $request->validate([
            'indicator_id' => ['required', 'exists:tilkit_indicators,id'],
            'site_id' => ['required', 'exists:tilkit_sites,id'],
            'season_id' => ['required', 'exists:tilkit_seasons,id'],
            'collection_date' => ['required', 'date'],
            'collection_year' => ['required', 'integer', 'min:1990', 'max:2100'],
            'workbook' => ['nullable', 'array'],
            'workbook.*' => ['nullable'],
            'workbook_photos' => ['nullable', 'array'],
            'workbook_photos.*' => ['file', 'image', 'max:8192'],
        ]);

        $this->assertToolOwnsReferences($tool, $validated);
        $this->assertIndicatorAllowedForCapture($tool, $validated, app(SiteIndicatorCatalogService::class));

        $record = TilkitIndicatorRecord::query()->create([
            'indicator_id' => $validated['indicator_id'],
            'site_id' => $validated['site_id'],
            'season_id' => $validated['season_id'],
            'collection_date' => $validated['collection_date'],
            'collection_year' => (int) $validated['collection_year'],
            'entered_by' => $request->user()->id,
        ]);

        $raw = WorkbookCapturePreparer::normalizeIncoming($validated['workbook'] ?? []);
        $workbook->storeRawValues($record, $raw);
        $workbook->storeReferenceColumns($record->fresh());
        $this->storePhotos($request, $record);

        return redirect()
            ->route('tools.tilkit.records.wizard', [$tool, $record, 'A'])
            ->with('status', 'Indicator record created - continue in the Community Data List wizard.');
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
            'sites' => $this->programs->sitesQuery($tool)->get(),
            'seasons' => $this->programs->seasonsQuery($tool)->get(),
            'indicators' => TilkitIndicator::query()->where('project_id', $tool->id)->orderBy('indicator_uid')->get(),
            'letterBands' => WorkbookColumnRegistry::rawColumnBandsForForm(),
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

        $raw = WorkbookCapturePreparer::normalizeIncoming($validated['workbook'] ?? []);
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
        $columns = $this->wizardColumnsForSection($section);
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
        $raw = WorkbookCapturePreparer::normalizeIncoming($validated['workbook'] ?? []);
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
        $site = TilkitSite::query()->where('project_id', $tool->id)->where('id', $validated['site_id'])->first();
        $season = TilkitSeason::query()->where('project_id', $tool->id)->where('id', $validated['season_id'])->first();
        abort_unless($site !== null && $season !== null, 422);
        abort_unless(
            $site->program_id !== null
            && $season->program_id !== null
            && (int) $site->program_id === (int) $season->program_id,
            422,
            'Site and season must belong to the same program.',
        );
        if ($season->site_id !== null) {
            abort_unless(
                (int) $season->site_id === (int) $site->id,
                422,
                'Season must belong to the selected site.',
            );
        }
    }

    /** @param  array<string, mixed>  $validated */
    private function assertIndicatorAllowedForCapture(DcProject $tool, array $validated, SiteIndicatorCatalogService $catalog): void
    {
        $indicator = TilkitIndicator::query()
            ->where('project_id', $tool->id)
            ->where('id', $validated['indicator_id'])
            ->first();
        abort_unless($indicator !== null, 422);

        $site = TilkitSite::query()->where('project_id', $tool->id)->where('id', $validated['site_id'])->first();
        $season = TilkitSeason::query()->where('project_id', $tool->id)->where('id', $validated['season_id'])->first();
        abort_unless($site !== null && $season !== null, 422);

        $month = $catalog->collectionMonthFromDate((string) $validated['collection_date']);
        $allowed = $catalog->allowedCodeIdsForCapture($site, $season, $month);

        abort_unless(
            in_array((int) $indicator->indicator_code_id, $allowed, true),
            422,
            'This indicator is not in the site catalogue for the selected site, season, and collection date.',
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

    /** @return list<array<string, mixed>> */
    private function wizardColumnsForSection(string $section): array
    {
        $letters = CommunityDataListRegistry::columnLettersForSectionKey($section);
        if ($section === 'B') {
            $letters = $this->insertColumnLettersAfter($letters, 'O', ['CI', 'CK', 'CL']);
        }

        $order = [];
        foreach (WorkbookColumnRegistry::rawColumns() as $i => $col) {
            $order[$col['column']] = $i;
        }

        $columns = array_values(array_filter(
            WorkbookColumnRegistry::rawColumns(),
            fn (array $col) => in_array($col['column'], $letters, true)
        ));

        usort($columns, fn (array $a, array $b) => ($order[$a['column']] ?? 999) <=> ($order[$b['column']] ?? 999));

        return $columns;
    }

    /** @param  list<string>  $letters  @param  list<string>  $insert  @return list<string> */
    private function insertColumnLettersAfter(array $letters, string $after, array $insert): array
    {
        $pos = array_search($after, $letters, true);
        if ($pos === false) {
            return array_values(array_unique([...$letters, ...$insert]));
        }

        array_splice($letters, $pos + 1, 0, $insert);

        return array_values(array_unique($letters));
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
