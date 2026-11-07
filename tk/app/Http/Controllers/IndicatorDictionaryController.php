<?php

namespace App\Http\Controllers;

use App\Support\Tilkit\IndicatorDictionaryPresenter;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class IndicatorDictionaryController extends Controller
{
    public function __invoke(IndicatorDictionaryPresenter $presenter): View
    {
        $dictionary = $presenter->build();

        $weatherTool = null;
        if (WeatherTool::singleToolMode()) {
            if (auth()->user()) {
                $weatherTool = WeatherTool::ensurePlatformTool(auth()->user());
            } elseif (Schema::hasTable('dc_projects')) {
                $weatherTool = WeatherTool::resolve();
            }
        }

        $title = 'TILKIT indicator dictionary - categories, codes, and workbook levels';
        $metaDescription = 'Browse the v4 indicator hierarchy: six categories, seven type lines, 157 legend codes, column A–D ownership, and domain-consistent behaviours.';

        return view('indicator-dictionary.index', compact('dictionary', 'weatherTool', 'title', 'metaDescription'));
    }
}
