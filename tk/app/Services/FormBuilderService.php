<?php

namespace App\Services;

use App\Models\DcForm;
use App\Models\DcFormField;
use App\Models\DcFormVersion;
use App\Models\DcProject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormBuilderService
{
    public function createForm(DcProject $project, User $user, array $data): DcForm
    {
        return DB::transaction(function () use ($project, $user, $data): DcForm {
            $code = Str::lower(Str::slug($data['code'] ?? $data['title']));
            $code = $this->uniqueFormCode($code);

            $form = DcForm::create([
                'uuid' => (string) Str::uuid(),
                'project_id' => $project->id,
                'code' => $code,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'record_kind' => $data['record_kind'] ?? 'record',
                'allow_anonymous' => (bool) ($data['allow_anonymous'] ?? false),
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            $version = DcFormVersion::create([
                'form_id' => $form->id,
                'version_no' => 1,
                'status' => 'draft',
                'created_at' => now(),
                'created_by' => $user->id,
            ]);

            $form->update(['current_version_id' => $version->id]);

            return $form->fresh(['currentVersion']);
        });
    }

    /** @param array<int, array<string, mixed>> $fields */
    public function syncDraftFields(DcFormVersion $version, array $fields): void
    {
        if ($version->status === 'published') {
            abort(422, 'Published versions are immutable. Create a new version to edit.');
        }

        DB::transaction(function () use ($version, $fields): void {
            DcFormField::where('form_version_id', $version->id)->delete();

            foreach ($fields as $index => $field) {
                DcFormField::create([
                    'form_version_id' => $version->id,
                    'field_type' => $field['field_type'],
                    'name' => $field['name'],
                    'label' => $field['label'] ?? $field['name'],
                    'hint' => $field['hint'] ?? null,
                    'position' => $field['position'] ?? $index,
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'parameters' => $field['parameters'] ?? null,
                    'sensitivity' => $field['sensitivity'] ?? 'none',
                ]);
            }

            $version->update([
                'compiled_json' => $this->compile($version->fresh('fields')),
            ]);
        });
    }

    public function publish(DcForm $form, User $user, ?string $changeNote = null): DcFormVersion
    {
        $version = $form->currentVersion()->with('fields')->firstOrFail();

        if ($version->fields->isEmpty()) {
            abort(422, 'Add at least one field before publishing.');
        }

        $version->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $user->id,
            'change_note' => $changeNote,
            'compiled_json' => $this->compile($version),
            'checksum' => hash('sha256', json_encode($this->compile($version))),
        ]);

        $form->update(['status' => 'published']);

        return $version->fresh();
    }

    public function compile(DcFormVersion $version): array
    {
        $version->loadMissing('fields.fieldTypeDefinition');

        return [
            'form_id' => $version->form_id,
            'version_no' => $version->version_no,
            'fields' => $version->fields->map(fn (DcFormField $f) => [
                'name' => $f->name,
                'type' => $f->field_type,
                'family' => $f->fieldTypeDefinition?->family,
                'label' => $f->label,
                'hint' => $f->hint,
                'required' => $f->is_required,
                'parameters' => $f->parameters,
                'sensitivity' => $f->sensitivity,
            ])->values()->all(),
        ];
    }

    private function uniqueFormCode(string $base): string
    {
        $code = Str::limit($base, 60, '');
        $suffix = 1;
        while (DcForm::where('code', $code)->exists()) {
            $code = Str::limit($base, 55, '').'_'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
