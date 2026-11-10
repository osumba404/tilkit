<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\KenyaDpa\PrivacyNoticeBuilder;
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
            'inventory' => config('kenya_dpa.tilkit_personal_data_columns', []),
            'lawfulBasis' => config('kenya_dpa.lawful_bases.'.$tool->lawful_basis),
        ]);
    }
}
