<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Services\PublicFormValidationService;
use App\Services\SubmissionIngestService;
use App\Support\FormWizardPages;
use App\Support\PublicFormAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(DcForm $form): View
    {
        abort_unless($form->status === 'published', 404);

        $form->load(['currentVersion', 'project']);
        $liveVersion = $form->liveVersion();
        abort_unless($liveVersion, 404);
        $liveVersion->load(['fields.fieldTypeDefinition']);
        $pages = FormWizardPages::pagesForForm($form, $liveVersion);

        return view('public.form', compact('form', 'liveVersion', 'pages'));
    }

    public function submit(
        Request $request,
        DcForm $form,
        SubmissionIngestService $ingest,
        PublicFormValidationService $validation,
    ): RedirectResponse {
        abort_unless($form->status === 'published', 404);

        if (! PublicFormAccess::canFill($form, $request->user())) {
            return redirect()
                ->guest(route('login'))
                ->with('status', 'Sign in to submit this form, or use the tool\'s public collection link.');
        }

        $request->attributes->set('submission_channel', 'web');

        $version = $form->liveVersion();
        abort_unless($version, 404);
        $version->load('fields');
        $rules = $validation->rulesForFields($version->fields);
        $validated = $request->validate($rules);
        $validated = $validation->normalizeDateTimeAnswers($version->fields, $validated);

        $ingest->submit($form, $validated, $request, $request->user());

        return redirect()
            ->route('public.forms.thanks', $form)
            ->with('status', 'Thank you - your response was recorded.');
    }

    public function thanks(DcForm $form): View
    {
        return view('public.thanks', compact('form'));
    }
}
