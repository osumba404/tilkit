<?php

namespace App\Http\Controllers;

use App\Support\Tilkit\WeatherTool;
use Illuminate\View\View;

class UserGuideController extends Controller
{
    public function __invoke(): View
    {
        $weatherTool = WeatherTool::singleToolMode()
            ? (auth()->user() ? WeatherTool::ensurePlatformTool(auth()->user()) : WeatherTool::resolve())
            : null;

        $title = 'User guide - TILKIT Weather Indicator Log on Tilkit';
        $metaDescription = 'Step-by-step guide: setup programs, sites and seasons, capture indicator records, validation, seasonal tercile tally, data desk, exports, and team roles.';

        return view('user-guide.index', compact('weatherTool', 'title', 'metaDescription'));
    }
}
