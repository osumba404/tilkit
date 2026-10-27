<?php

namespace App\Http\Controllers;

use App\Support\Marketing\HomeTilkitAbout;
use App\Support\Tilkit\WeatherTool;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $weatherTool = WeatherTool::singleToolMode()
            ? (auth()->user() ? WeatherTool::ensurePlatformTool(auth()->user()) : WeatherTool::resolve())
            : null;

        $about = HomeTilkitAbout::content();
        $title = 'Tilkit - institutional data collection and field analytics';
        $metaDescription = HomeTilkitAbout::metaDescriptionShort();
        $ogDescription = HomeTilkitAbout::metaDescriptionSocial();
        $metaKeywords = implode(', ', HomeTilkitAbout::keywords());

        $jsonLd = HomeTilkitAbout::jsonLdGraph(
            route('home'),
            $title,
            config('app.name', 'Tilkit'),
        );

        return view('home', compact(
            'weatherTool',
            'about',
            'title',
            'metaDescription',
            'ogDescription',
            'metaKeywords',
            'jsonLd',
        ));
    }
}
