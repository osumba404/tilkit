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
        $metaDescription = 'Plain-language guide to Tilkit: sign in, set up your programme, capture community weather signs, check quality, run the seasonal summary, and export data safely.';

        return view('user-guide.index', compact('weatherTool', 'title', 'metaDescription'));
    }
}
