<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicToolConsentController extends Controller
{
    public function show(DcProject $tool): View|RedirectResponse
    {
        abort_unless($tool->public_collection_enabled, 404);

        if (! $tool->requiresConsentGate()) {
            return redirect()->route('public.tools.collect', $tool);
        }

        if (session('tool_consent_'.$tool->id)) {
            return redirect()->route('public.tools.collect', $tool);
        }

        app()->setLocale($tool->default_locale ?: 'en');

        return view('public.consent', compact('tool'));
    }

    public function accept(Request $request, DcProject $tool): RedirectResponse
    {
        abort_unless($tool->public_collection_enabled, 404);
        abort_unless($tool->requiresConsentGate(), 404);

        $rules = [
            'consent_ack' => ['accepted'],
        ];

        if ($tool->consent_require_signature) {
            $rules['consent_signature'] = ['required', 'string', 'max:500000'];
        }

        $request->validate($rules);

        session([
            'tool_consent_'.$tool->id => [
                'at' => now()->toIso8601String(),
                'signature' => $tool->consent_require_signature ? $request->string('consent_signature') : null,
            ],
        ]);

        return redirect()->route('public.tools.collect', $tool);
    }
}
