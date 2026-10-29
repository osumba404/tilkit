<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Services\Tilkit\TilkitProgramContext;
use App\Services\ToolAccessService;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** JSON endpoints for the offline-first Flutter field app (Brief §8, Community Data List B). */
class TilkitMobileController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        return response()->json([
            'status' => 'ok',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'ok']);
    }

    public function bootstrap(Request $request, ToolAccessService $access, TilkitProgramContext $programs): JsonResponse
    {
        $tool = WeatherTool::resolve();
        abort_if($tool === null, 503, 'Weather tool not configured.');
        $access->assertCanView($request->user(), $tool);

        $programList = TilkitProgram::query()
            ->where('project_id', $tool->id)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        $activeProgram = $programs->active($tool);
        $activeProgramId = $activeProgram?->id ?? $programList->first()?->id;

        if ($request->filled('program_id')) {
            $programs->setActive($tool, $request->integer('program_id'));
            $activeProgramId = $request->integer('program_id');
            $activeProgram = $programList->firstWhere('id', $activeProgramId);
        }

        $sites = $programs->sitesQuery($tool)->orderBy('name')->get(['id', 'name', 'latitude', 'longitude']);
        $seasons = $programs->seasonsQuery($tool)->get()->map(fn ($s) => [
            'id' => $s->id,
            'site_id' => $s->site_id,
            'label' => $s->monthRangeLabel(),
        ]);

        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with([
                'indicator:id,indicator_uid,local_name',
                'site:id,name',
                'season:id,label,start_month,end_month',
            ])
            ->orderByDesc('collection_date')
            ->limit(500)
            ->get()
            ->map(fn (TilkitIndicatorRecord $r) => [
                'uuid' => $r->uuid,
                'indicator_uid' => $r->indicator?->indicator_uid,
                'indicator_name' => $r->indicator?->local_name,
                'site_id' => $r->site_id,
                'site_name' => $r->site?->name,
                'season_id' => $r->season_id,
                'season_label' => $r->season?->monthRangeLabel(),
                'collection_year' => $r->collection_year,
                'sighted' => (bool) $r->sighted,
            ]);

        return response()->json([
            'tool' => ['id' => $tool->id, 'code' => $tool->code, 'name' => $tool->name],
            'protocol_version' => config('tilkit.weather.protocol_version'),
            'programs' => $programList,
            'active_program_id' => $activeProgramId,
            'active_program_name' => $activeProgram?->name,
            'sites' => $sites,
            'seasons' => $seasons,
            'records' => $records,
        ]);
    }
}
