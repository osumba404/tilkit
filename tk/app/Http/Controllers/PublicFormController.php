<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Services\SubmissionIngestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(DcForm $form): View
    {
        abort_unless($form->status === 'published', 404);

        $form->load(['currentVersion.fields.fieldTypeDefinition', 'project']);

        return view('public.form', compact('form'));
    }

    public function submit(Request $request, DcForm $form, SubmissionIngestService $ingest): RedirectResponse
    {
        abort_unless($form->status === 'published', 404);

        if (! $form->allow_anonymous && ! $request->user()) {
            return redirect()->route('login')->with('status', 'Sign in to submit this form.');
        }

        $request->attributes->set('submission_channel', 'web');

        $version = $form->currentVersion()->with('fields')->firstOrFail();
        $rules = [];
        foreach ($version->fields as $field) {
            $rules[$field->name] = $field->is_required ? ['required'] : ['nullable'];
        }

        $validated = $request->validate($rules);

        $ingest->submit($form, $validated, $request, $request->user());

        return redirect()
            ->route('public.forms.thanks', $form)
            ->with('status', 'Thank you — your response was recorded.');
    }

    public function thanks(DcForm $form): View
    {
        return view('public.thanks', compact('form'));
    }
}
