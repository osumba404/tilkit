<?php

namespace App\Http\Controllers;

use App\Enums\Tilkit\ValidationStatus;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitSighting;
use App\Models\Tilkit\TilkitTallySnapshot;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\WorkbookRecordService;
use App\Services\ToolAccessService;
use App\Support\Tilkit\WorkbookColumnRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolTilkitAdminController extends Controller
{
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
        ]);
        $settings = TilkitSeasonSettings::query()->updateOrCreate(
            ['site_id' => $data['site_id'], 'season_id' => $data['season_id']],
            [
                'use_prior_multiplier' => $request->boolean('use_prior_multiplier'),
                'settings' => TilkitSeasonSettings::defaultSettings(),
                'protocol_version' => config('tilkit.weather.protocol_version'),
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

    public function dataDesk(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanView($request->user(), $tool);

        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['indicator', 'site', 'season', 'workbookValues'])
            ->latest('updated_at')
            ->limit(100)
            ->get()
            ->map(function (TilkitIndicatorRecord $record) {
                $ce = $record->workbookValues->firstWhere('column_letter', 'CE');

                return [
                    'record' => $record,
                    'tally_status' => $ce?->value_text ?? '—',
                    'needs_confirm' => ! $record->confirmed,
                    'needs_approve' => ! $record->approved,
                    'contested' => $record->validation_status === ValidationStatus::Contested,
                ];
            });

        $pending = $records->filter(fn ($r) => $r['needs_confirm'] || $r['needs_approve'] || $r['contested']);

        return view('tools.tilkit.data-desk', compact('tool', 'records', 'pending'));
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

    public function exportCsv(Request $request, DcProject $tool, ToolAccessService $access, WorkbookRecordService $workbook): StreamedResponse
    {
        $access->assertCanView($request->user(), $tool);

        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['workbookValues', 'indicator'])
            ->get();

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

    private function assertRecordBelongsToTool(DcProject $tool, TilkitIndicatorRecord $record): void
    {
        abort_unless($record->indicator && $record->indicator->project_id === $tool->id, 404);
    }
}
