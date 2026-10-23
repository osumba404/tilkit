<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitSighting;
use App\Models\Tilkit\TilkitTallySnapshot;
use App\Models\Tilkit\TilkitConsensusForecast;
use App\Services\Tilkit\DataDeskService;
use App\Services\Tilkit\TilkitConsentGate;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\WorkbookRecordService;
use App\Services\ToolAccessService;
use App\Support\Tilkit\CommunityDataListRegistry;
use App\Support\Tilkit\WorkbookColumnRegistry;
use App\Support\Tilkit\WorkbookSheetCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolTilkitAdminController extends Controller
{
    public function workbookGuides(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        return view('tools.tilkit.workbook-guides', [
            'tool' => $tool,
            'sheets' => WorkbookSheetCatalog::sheets(),
            'communitySections' => CommunityDataListRegistry::bySection(),
        ]);
    }

    public function calendar(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['indicator', 'site', 'season', 'workbookValues'])
            ->get()
            ->map(function (TilkitIndicatorRecord $record) {
                $vals = $record->workbookValues->keyBy('column_letter');

                return [
                    'record' => $record,
                    'ci' => $vals->get('CI')?->value_date?->format('Y-m-d') ?? $vals->get('CI')?->value_text,
                    'ck' => $vals->get('CK')?->value_text,
                    'cl' => $vals->get('CL')?->value_number ?? $vals->get('CL')?->value_text,
                    'cm' => $vals->get('CM')?->value_number,
                    'cn' => $vals->get('CN')?->value_text,
                    'co' => $vals->get('CO')?->value_text,
                    'cp' => $vals->get('CP')?->value_text,
                ];
            });

        return view('tools.tilkit.calendar', compact('tool', 'records'));
    }

    public function setup(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        return view('tools.tilkit.setup', [
            'tool' => $tool,
            'sites' => TilkitSite::query()->where('project_id', $tool->id)->orderBy('name')->get(),
            'seasons' => TilkitSeason::query()->where('project_id', $tool->id)->orderBy('label')->get(),
            'indicators' => TilkitIndicator::query()->where('project_id', $tool->id)->with('code')->orderBy('indicator_uid')->get(),
            'codes' => TilkitIndicatorCode::query()->orderBy('code')->limit(200)->get(),
        ]);
    }

    public function storeSite(Request $request, DcProject $tool, ToolAccessService $access): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'ward' => ['nullable', 'string', 'max:120'],
            'county' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'size:2'],
            'primary_language' => ['nullable', 'string', 'max:40'],
        ]);
        TilkitSite::query()->create([...$data, 'project_id' => $tool->id, 'country' => $data['country'] ?? 'KE']);

        return back()->with('status', 'Site added.');
    }

    public function storeSeason(Request $request, DcProject $tool, ToolAccessService $access): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
        ]);
        TilkitSeason::query()->create([
            ...$data,
            'project_id' => $tool->id,
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);

        return back()->with('status', 'Season added.');
    }

    public function storeIndicator(Request $request, DcProject $tool, ToolAccessService $access): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $data = $request->validate([
            'indicator_uid' => ['required', 'string', 'max:32', 'unique:tilkit_indicators,indicator_uid'],
            'indicator_code_id' => ['required', 'exists:tilkit_indicator_codes,id'],
            'local_name' => ['nullable', 'string', 'max:200'],
        ]);
        TilkitIndicator::query()->create([...$data, 'project_id' => $tool->id]);

        return back()->with('status', 'Indicator registered.');
    }

    public function seasonSettings(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);
        $sites = TilkitSite::query()->where('project_id', $tool->id)->orderBy('name')->get();
        $seasons = TilkitSeason::query()->where('project_id', $tool->id)->orderBy('label')->get();
        $siteId = (int) $request->query('site_id', $sites->first()?->id);
        $seasonId = (int) $request->query('season_id', $seasons->first()?->id);
        $settings = null;
        if ($siteId && $seasonId) {
            $settings = TilkitSeasonSettings::query()->firstOrCreate(
                ['site_id' => $siteId, 'season_id' => $seasonId],
                ['use_prior_multiplier' => false, 'settings' => TilkitSeasonSettings::defaultSettings()]
            );
        }

        return view('tools.tilkit.season-settings', compact('tool', 'sites', 'seasons', 'siteId', 'seasonId', 'settings'));
    }

    public function updateSeasonSettings(Request $request, DcProject $tool, ToolAccessService $access): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $data = $request->validate([
            'site_id' => ['required', 'exists:tilkit_sites,id'],
            'season_id' => ['required', 'exists:tilkit_seasons,id'],
            'use_prior_multiplier' => ['nullable', 'boolean'],
            'kmsa_outlook_tercile' => ['nullable', 'integer', 'in:0,1,2,3'],
            'ndma_drought_phase' => ['nullable', 'string', 'max:40'],
            'ndma_vci3m' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $settings = TilkitSeasonSettings::query()->updateOrCreate(
            ['site_id' => $data['site_id'], 'season_id' => $data['season_id']],
            [
                'use_prior_multiplier' => $request->boolean('use_prior_multiplier'),
                'settings' => TilkitSeasonSettings::defaultSettings(),
                'protocol_version' => config('tilkit.weather.protocol_version'),
                'kmsa_outlook_tercile' => $data['kmsa_outlook_tercile'] ?? null,
                'ndma_drought_phase' => $data['ndma_drought_phase'] ?? null,
                'ndma_vci3m' => isset($data['ndma_vci3m']) ? (float) $data['ndma_vci3m'] : null,
            ]
        );
        app(TilkitEngineService::class)->recalculateSiteSeason($settings->site_id, $settings->season_id);

        return redirect()->route('tools.tilkit.settings', [$tool, 'site_id' => $data['site_id'], 'season_id' => $data['season_id']])
            ->with('status', 'Season settings saved; records recalculated.');
    }

    public function tally(Request $request, DcProject $tool, ToolAccessService $access, TilkitEngineService $engine): View|RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $sites = TilkitSite::query()->where('project_id', $tool->id)->orderBy('name')->get();
        $seasons = TilkitSeason::query()->where('project_id', $tool->id)->orderBy('label')->get();

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'site_id' => ['required', 'exists:tilkit_sites,id'],
                'season_id' => ['required', 'exists:tilkit_seasons,id'],
                'publish' => ['nullable', 'boolean'],
            ]);
            $result = $engine->runTally(
                (int) $data['site_id'],
                (int) $data['season_id'],
                $request->boolean('publish'),
                $request->user()->id,
            );

            return view('tools.tilkit.tally', [
                'tool' => $tool,
                'sites' => $sites,
                'seasons' => $seasons,
                'siteId' => (int) $data['site_id'],
                'seasonId' => (int) $data['season_id'],
                'result' => $result,
                'snapshots' => TilkitTallySnapshot::query()
                    ->where('site_id', $data['site_id'])
                    ->where('season_id', $data['season_id'])
                    ->latest('id')
                    ->limit(5)
                    ->get(),
            ]);
        }

        return view('tools.tilkit.tally', [
            'tool' => $tool,
            'sites' => $sites,
            'seasons' => $seasons,
            'siteId' => (int) $request->query('site_id', $sites->first()?->id),
            'seasonId' => (int) $request->query('season_id', $seasons->first()?->id),
            'result' => null,
            'snapshots' => collect(),
        ]);
    }

    public function dataDesk(Request $request, DcProject $tool, ToolAccessService $access, DataDeskService $desk): View
    {
        $access->assertCanView($request->user(), $tool);

        $records = $desk->queueForTool($tool);
        $pending = $records->filter(fn ($r) => $r['queue'] !== 'ok');
        $byQueue = $records->groupBy('queue');

        return view('tools.tilkit.data-desk', compact('tool', 'records', 'pending', 'byQueue'));
    }

    public function storeSighting(Request $request, DcProject $tool, TilkitIndicatorRecord $record, ToolAccessService $access, WorkbookRecordService $workbook): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $this->assertRecordBelongsToTool($tool, $record);

        $data = $request->validate([
            'sighted_at' => ['required', 'date'],
            'place_text' => ['nullable', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:200'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'observed_by' => ['nullable', 'string', 'max:200'],
        ]);

        TilkitSighting::query()->create([
            ...$data,
            'indicator_record_id' => $record->id,
            'created_by' => $request->user()->id,
        ]);

        $record->update(['sighted' => true]);
        $workbook->storeRawValues($record, [
            'N' => 1,
            'O' => $data['sighted_at'],
            'CI' => $data['sighted_at'],
            'Z' => $data['place_text'] ?? null,
            'AA' => isset($data['latitude'], $data['longitude']) ? "{$data['latitude']},{$data['longitude']}" : null,
            'AB' => $data['landmark'] ?? null,
        ]);

        return back()->with('status', 'Sighting recorded on this indicator row.');
    }

    public function exportCsv(Request $request, DcProject $tool, ToolAccessService $access, WorkbookRecordService $workbook, TilkitConsentGate $consent): StreamedResponse
    {
        $access->assertCanView($request->user(), $tool);

        $records = $this->exportableRecords($tool, $consent, $request->boolean('include_blocked'));

        $columns = WorkbookColumnRegistry::all();

        return response()->streamDownload(function () use ($records, $workbook, $columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_map(fn ($c) => $c['column'].' '.$c['field'], $columns));
            foreach ($records as $record) {
                $map = $workbook->rawWorkbookMap($record);
                $derived = [];
                foreach ($record->workbookValues->where('is_derived', true) as $cell) {
                    $derived[$cell->column_letter] = $cell->value_number ?? $cell->value_text;
                }
                $row = [];
                foreach ($columns as $col) {
                    $letter = $col['column'];
                    $row[] = $col['derived']
                        ? ($derived[$letter] ?? '')
                        : ($map[$letter] ?? '');
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'tilkit-workbook-export.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportJson(Request $request, DcProject $tool, ToolAccessService $access, WorkbookRecordService $workbook, TilkitConsentGate $consent): StreamedResponse
    {
        $access->assertCanView($request->user(), $tool);
        $records = $this->exportableRecords($tool, $consent, false);
        $columns = WorkbookColumnRegistry::all();
        $payload = [];
        foreach ($records as $record) {
            $map = $workbook->rawWorkbookMap($record);
            $row = ['indicator_uid' => $record->indicator?->indicator_uid];
            foreach ($columns as $col) {
                $letter = $col['column'];
                if ($col['derived']) {
                    $cell = $record->workbookValues->firstWhere('column_letter', $letter);
                    $row[$letter] = $cell?->value_number ?? $cell?->value_text;
                } else {
                    $row[$letter] = $map[$letter] ?? null;
                }
            }
            $payload[] = $row;
        }

        return response()->streamDownload(
            fn () => print (json_encode(['protocol' => config('tilkit.weather.protocol_version'), 'rows' => $payload], JSON_PRETTY_PRINT)),
            'tilkit-workbook-export.json',
            ['Content-Type' => 'application/json'],
        );
    }

    public function publishConsensus(Request $request, DcProject $tool, ToolAccessService $access, TilkitEngineService $engine): RedirectResponse
    {
        $access->assertCanView($request->user(), $tool);
        $data = $request->validate([
            'site_id' => ['required', 'exists:tilkit_sites,id'],
            'season_id' => ['required', 'exists:tilkit_seasons,id'],
            'integrated_tercile' => ['required', 'integer', 'in:1,2,3'],
            'advisory_message' => ['required', 'string', 'max:5000'],
            'publish_snapshot' => ['nullable', 'boolean'],
        ]);
        $result = $engine->runTally((int) $data['site_id'], (int) $data['season_id'], $request->boolean('publish_snapshot'), $request->user()->id);
        $settings = TilkitSeasonSettings::query()->where('site_id', $data['site_id'])->where('season_id', $data['season_id'])->first();
        $forecast = TilkitConsensusForecast::query()->create([
            'site_id' => $data['site_id'],
            'season_id' => $data['season_id'],
            'label' => 'Integration '.now()->format('Y-m-d H:i'),
            'community_tercile' => $result->leadingWeighted ?? $result->leadingSimple,
            'kmsa_tercile' => $settings?->kmsa_outlook_tercile,
            'integrated_tercile' => (int) $data['integrated_tercile'],
            'advisory_message' => $data['advisory_message'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('tools.tilkit.tally', [$tool, 'site_id' => $data['site_id'], 'season_id' => $data['season_id']])
            ->with('status', "Consensus forecast #{$forecast->id} published (BF).");
    }

    /** @return \Illuminate\Support\Collection<int, TilkitIndicatorRecord> */
    private function exportableRecords(DcProject $tool, TilkitConsentGate $consent, bool $includeBlocked): \Illuminate\Support\Collection
    {
        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['workbookValues', 'indicator'])
            ->get();

        if ($includeBlocked) {
            return $records;
        }

        return $records->filter(fn (TilkitIndicatorRecord $r) => $consent->canExport($r));
    }

    private function assertRecordBelongsToTool(DcProject $tool, TilkitIndicatorRecord $record): void
    {
        abort_unless($record->indicator && $record->indicator->project_id === $tool->id, 404);
    }
}
