<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\PublicFormValidationService;
use App\Services\SubmissionIngestService;
use App\Support\FormWizardPages;
use App\Support\PublicFormAccess;
use App\Support\QrCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PublicToolController extends Controller
{
    public function collect(DcProject $tool): View
    {
        abort_unless($tool->public_collection_enabled, 404);

        $tool->load([
            'forms' => fn ($q) => $q->where('status', 'published')->orderBy('id'),
        ]);

        $pages = FormWizardPages::pagesForTool($tool);
        $publicUrl = $tool->publicCollectUrl();

        return view('public.tool-collect', compact('tool', 'publicUrl', 'pages'));
    }

    public function submit(
        Request $request,
        DcProject $tool,
        PublicFormValidationService $validation,
        SubmissionIngestService $ingest,
    ): RedirectResponse {
        abort_unless($tool->public_collection_enabled, 404);

        $tool->load([
            'forms' => fn ($q) => $q->where('status', 'published')->orderBy('id'),
        ]);

        $pages = FormWizardPages::pagesForTool($tool);
        abort_if($pages === [], 404);

        $firstForm = $tool->forms->first();
        if ($firstForm && ! PublicFormAccess::canFill($firstForm, $request->user())) {
            return redirect()
                ->guest(route('login'))
                ->with('status', 'Sign in to submit, or use the public collection link without restrictions.');
        }

        $allFields = FormWizardPages::allFields($pages);
        $rules = $validation->rulesForFields($allFields);
        $validated = $request->validate($rules);
        $validated = $validation->normalizeDateTimeAnswers($allFields, $validated);

        DB::transaction(function () use ($tool, $validated, $request, $ingest): void {
            foreach ($tool->forms as $form) {
                if ($form->status !== 'published') {
                    continue;
                }

                if (! PublicFormAccess::canFill($form, $request->user())) {
                    continue;
                }

                $version = $form->liveVersion();
                if (! $version) {
                    continue;
                }

                $version->load('fields');
                $names = $version->fields
                    ->reject(fn ($f) => in_array($f->field_type, ['note', 'group', 'repeat'], true)
                        || ($f->field_type === 'note' && str_starts_with($f->name, 'section_')))
                    ->pluck('name')
                    ->all();

                $subset = array_intersect_key($validated, array_flip($names));
                if ($subset === []) {
                    continue;
                }

                $request->attributes->set('submission_channel', 'web');
                $ingest->submit($form, $subset, $request, $request->user());
            }
        });

        return redirect()
            ->route('public.tools.thanks', $tool)
            ->with('status', 'Thank you — your responses were recorded.');
    }

    public function thanks(DcProject $tool): View
    {
        abort_unless($tool->public_collection_enabled, 404);

        return view('public.tool-thanks', compact('tool'));
    }

    public function qr(DcProject $tool): Response
    {
        abort_unless($tool->public_collection_enabled, 404);

        $svg = QrCodeGenerator::svg($tool->publicCollectUrl());

        return response($svg, HttpResponse::HTTP_OK, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
