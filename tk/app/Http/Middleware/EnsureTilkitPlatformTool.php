<?php

namespace App\Http\Middleware;

use App\Support\Tilkit\WeatherTool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single-tool deployments: ensure the canonical weather tool exists and the
 * signed-in user can open it (sites/seasons vary by study; the tool does not).
 */
class EnsureTilkitPlatformTool
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! WeatherTool::singleToolMode()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        WeatherTool::ensurePlatformTool($user);
        WeatherTool::ensureMember($user);

        return $next($request);
    }
}
