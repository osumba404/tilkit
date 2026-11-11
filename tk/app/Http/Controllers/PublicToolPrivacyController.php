<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\KenyaDpa\PrivacyNoticeBuilder;
use App\Support\Tilkit\WeatherTool;
use Illuminate\View\View;

class PublicToolPrivacyController extends Controller
{
    public function show(DcProject $tool, PrivacyNoticeBuilder $notices): View
    {
        $tool->load('owner');
        app()->setLocale($tool->default_locale ?: 'en');

        return view('public.privacy', [
            'tool' => $tool,
            'notice' => $notices->render($tool),
            'inventory' => WeatherTool::isWeatherModule($tool)
                ? config('kenya_dpa.tilkit_personal_data_columns', [])
                : [],
        ]);
    }
}
