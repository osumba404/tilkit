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

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array{version: DcFormVersion, forked: bool}
     */
    public function syncDraftFields(DcForm $form, ?User $user, array $fields): array
    {
        $forked = false;

        $version = DB::transaction(function () use ($form, $user, $fields, &$forked): DcFormVersion {
            $version = $this->ensureDraftVersion($form, $user, $forked);

            DcFormField::where('form_version_id', $version->id)->delete();

            $repeatIdsByName = [];

            foreach ($fields as $index => $field) {
                $parentId = null;
                $repeatParent = $field['repeat_parent'] ?? null;
                if ($repeatParent && isset($repeatIdsByName[$repeatParent])) {
                    $parentId = $repeatIdsByName[$repeatParent];
                }

                $created = DcFormField::create([
                    'form_version_id' => $version->id,
                    'parent_id' => $parentId,
                    'field_type' => $field['field_type'],
                    'name' => $field['name'],
                    'label' => $field['label'] ?? $field['name'],
                    'hint' => $field['hint'] ?? null,
                    'position' => $field['position'] ?? $index,
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'relevant_expr' => $field['relevant_expr'] ?? null,
                    'calculation_expr' => $field['calculation_expr'] ?? null,
                    'parameters' => $field['parameters'] ?? null,
                    'sensitivity' => $field['sensitivity'] ?? 'none',
                ]);

                if ($field['field_type'] === 'repeat') {
                    $repeatIdsByName[$field['name']] = $created->id;
                }
            }

            $version->update([
                'compiled_json' => $this->compile($version->fresh('fields')),
            ]);

            return $version->fresh('fields');
        });

        return ['version' => $version, 'forked' => $forked];
    }

    public function ensureDraftVersion(DcForm $form, ?User $user, ?bool &$forked = null): DcFormVersion
    {
        $form->loadMissing('currentVersion.fields');
        $current = $form->currentVersion ?? abort(404, 'Form has no version.');

        if ($current->status === 'draft') {
            return $current;
        }

        if ($forked !== null) {
            $forked = true;
        }

        return DB::transaction(function () use ($form, $current, $user): DcFormVersion {
            $nextNo = ((int) $form->versions()->max('version_no')) + 1;

            $draft = DcFormVersion::create([
                'form_id' => $form->id,
                'version_no' => $nextNo,
                'status' => 'draft',
                'change_note' => 'Draft branched from published version '.$current->version_no,
                'created_at' => now(),
                'created_by' => $user?->id,
            ]);

            $idMap = [];
            foreach ($current->fields as $field) {
                $created = DcFormField::create([
                    'form_version_id' => $draft->id,
                    'parent_id' => null,
                    'field_type' => $field->field_type,
                    'name' => $field->name,
                    'label' => $field->label,
                    'hint' => $field->hint,
                    'position' => $field->position,
                    'choice_list_id' => null,
                    'is_required' => $field->is_required,
                    'required_message' => $field->required_message,
                    'relevant_expr' => $field->relevant_expr,
                    'constraint_expr' => $field->constraint_expr,
                    'constraint_msg' => $field->constraint_msg,
                    'calculation_expr' => $field->calculation_expr,
                    'default_value' => $field->default_value,
                    'is_read_only' => $field->is_read_only,
                    'appearance' => $field->appearance,
                    'parameters' => $field->parameters,
                    'repeat_count_expr' => $field->repeat_count_expr,
                    'sensitivity' => $field->sensitivity,
                    'is_identifier' => $field->is_identifier,
                ]);
                $idMap[$field->id] = $created->id;
            }

            foreach ($current->fields as $field) {
                if ($field->parent_id && isset($idMap[$field->parent_id], $idMap[$field->id])) {
                    DcFormField::where('id', $idMap[$field->id])->update([
                        'parent_id' => $idMap[$field->parent_id],
                    ]);
                }
            }

            $form->update(['current_version_id' => $draft->id]);

            return $draft->fresh('fields');
        });
    }

    public function publish(DcForm $form, User $user, ?string $changeNote = null): DcFormVersion
    {
        $version = $form->currentVersion()->with('fields')->firstOrFail();

        if ($version->status !== 'draft') {
            abort(422, 'Only a draft version can be published.');
        }

        if ($version->fields->isEmpty()) {
            abort(422, 'Add at least one field before publishing.');
        }

        $version->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $user->id,
            'change_note' => $changeNote ?? $version->change_note,
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

    public function duplicateForm(DcForm $source, User $user): DcForm
    {
        return DB::transaction(function () use ($source, $user): DcForm {
            $source->load('currentVersion.fields');
            $copy = DcForm::create([
                'uuid' => (string) Str::uuid(),
                'project_id' => $source->project_id,
                'code' => $this->uniqueFormCode($source->code.'_copy'),
                'title' => $source->title.' (copy)',
                'description' => $source->description,
                'record_kind' => $source->record_kind,
                'allow_anonymous' => $source->allow_anonymous,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            $version = DcFormVersion::create([
                'form_id' => $copy->id,
                'version_no' => 1,
                'status' => 'draft',
                'created_at' => now(),
                'created_by' => $user->id,
            ]);

            $copy->update(['current_version_id' => $version->id]);

            $fields = $source->currentVersion?->fields ?? collect();
            $idMap = [];
            foreach ($fields as $field) {
                $created = DcFormField::create([
                    'form_version_id' => $version->id,
                    'parent_id' => null,
                    'field_type' => $field->field_type,
                    'name' => $field->name,
                    'label' => $field->label,
                    'hint' => $field->hint,
                    'position' => $field->position,
                    'is_required' => $field->is_required,
                    'relevant_expr' => $field->relevant_expr,
                    'calculation_expr' => $field->calculation_expr,
                    'parameters' => $field->parameters,
                    'sensitivity' => $field->sensitivity,
                ]);
                $idMap[$field->id] = $created->id;
            }

            foreach ($fields as $field) {
                if ($field->parent_id && isset($idMap[$field->parent_id], $idMap[$field->id])) {
                    DcFormField::where('id', $idMap[$field->id])->update([
                        'parent_id' => $idMap[$field->parent_id],
                    ]);
                }
            }

            $version->update(['compiled_json' => $this->compile($version->fresh('fields'))]);

            return $copy->fresh(['currentVersion']);
        });
    }

    /**
     * @return array{added: list<string>, removed: list<string>, changed: list<string>}
     */
    public function diffPublishedToDraft(DcForm $form): array
    {
        $draft = $form->currentVersion;
        $live = $form->liveVersion();
        if (! $draft || ! $live || $draft->id === $live->id) {
            return ['added' => [], 'removed' => [], 'changed' => []];
        }

        $draftNames = $draft->fields->pluck('name', 'name');
        $liveNames = $live->fields->pluck('name', 'name');

        $added = array_values(array_diff(array_keys($draftNames->all()), array_keys($liveNames->all())));
        $removed = array_values(array_diff(array_keys($liveNames->all()), array_keys($draftNames->all())));

        $changed = [];
        foreach ($draftNames as $name => $_) {
            if (! isset($liveNames[$name])) {
                continue;
            }
            $d = $draft->fields->firstWhere('name', $name);
            $l = $live->fields->firstWhere('name', $name);
            if ($d->field_type !== $l->field_type || $d->label !== $l->label || $d->is_required !== $l->is_required) {
                $changed[] = $name;
            }
        }

        return compact('added', 'removed', 'changed');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function importFieldsFromCsv(string $csvBody): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvBody)) ?: [];
        if ($lines === []) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $nameIdx = array_search('name', $header, true);
        $typeIdx = array_search('type', $header, true);
        $labelIdx = array_search('label', $header, true);

        if ($nameIdx === false || $typeIdx === false) {
            return [];
        }

        $fields = [];
        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            $fields[] = [
                'name' => Str::lower(trim($cols[$nameIdx] ?? 'field_'.($i + 1))),
                'field_type' => trim($cols[$typeIdx] ?? 'text'),
                'label' => trim($cols[$labelIdx !== false ? $labelIdx : 2] ?? $cols[$nameIdx] ?? ''),
                'hint' => '',
                'is_required' => false,
                'parameters' => [],
                'position' => count($fields),
            ];
        }

        return $fields;
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
