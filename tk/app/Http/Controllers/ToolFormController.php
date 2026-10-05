<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Models\DcProject;
use App\Services\FormBuilderService;
use App\Services\ToolAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolFormController extends Controller
{
    public function create(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanManage($request->user(), $tool);

        return view('tools.forms.create', compact('tool'));
    }

    public function store(Request $request, DcProject $tool, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:60'],
            'allow_anonymous' => ['sometimes', 'boolean'],
        ]);

        $form = $builder->createForm($tool, $request->user(), $validated);

        return redirect()->route('tools.forms.builder', [$tool, $form]);
    }

    public function builder(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access): View
    {
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $form->load(['currentVersion.fields.fieldTypeDefinition']);
        $fieldTypes = \App\Models\DcFieldType::query()->orderBy('family')->orderBy('code')->get();

        return view('tools.forms.builder', compact('tool', 'form', 'fieldTypes'));
    }

    public function saveBuilder(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_]*$/'],
            'fields.*.field_type' => ['required', 'exists:dc_field_types,code'],
            'fields.*.label' => ['nullable', 'string'],
            'fields.*.hint' => ['nullable', 'string'],
            'fields.*.is_required' => ['sometimes', 'boolean'],
        ]);

        $version = $form->currentVersion;
        $builder->syncDraftFields($version, $validated['fields']);

        return back()->with('status', 'Draft saved.');
    }

    public function publish(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $builder->publish($form, $request->user(), $request->input('change_note'));

        return redirect()->route('tools.show', $tool)->with('status', 'Form published and ready for collection.');
    }
}
