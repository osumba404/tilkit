<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <h2 class="font-serif text-2xl font-bold">Form builder — {{ $form->title }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('reference') }}" class="tk-btn-secondary text-sm" target="_blank">Field catalogue</a>
                @if ($form->currentVersion?->fields->isNotEmpty())
                    <form method="POST" action="{{ route('tools.forms.publish', [$tool, $form]) }}">
                        @csrf
                        <button type="submit" class="tk-btn-primary text-sm">Publish form</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div x-data="formBuilder(@js($form->currentVersion?->fields->map(fn($f) => [
        'name' => $f->name,
        'field_type' => $f->field_type,
        'label' => $f->label,
        'hint' => $f->hint,
        'is_required' => $f->is_required,
    ]) ?? []), @js($fieldTypes->groupBy('family')->map(function ($group, $family) {
        return [
            'family' => $family,
            'types' => $group->map(fn ($t) => ['code' => $t->code, 'catalogue_id' => $t->catalogue_id])->values(),
        ];
    })->values()))" class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 tk-card p-5">
            <form method="POST" action="{{ route('tools.forms.builder.save', [$tool, $form]) }}">
                @csrf
                <template x-for="(field, index) in fields" :key="index">
                    <div class="border border-tk-line rounded-md p-4 mb-3">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-tk-muted">Field name (key)</label>
                                <input :name="'fields['+index+'][name]'" x-model="field.name" required pattern="[a-z][a-z0-9_]*" class="w-full rounded border border-tk-line px-2 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-tk-muted">Type</label>
                                <select :name="'fields['+index+'][field_type]'" x-model="field.field_type" class="w-full rounded border border-tk-line px-2 py-1.5 text-sm">
                                    @foreach ($fieldTypes as $type)
                                        <option value="{{ $type->code }}">{{ $type->catalogue_id ?? $type->family }} — {{ $type->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-xs text-tk-muted">Label</label>
                                <input :name="'fields['+index+'][label]'" x-model="field.label" class="w-full rounded border border-tk-line px-2 py-1.5 text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                                <input type="checkbox" :name="'fields['+index+'][is_required]'" value="1" x-model="field.is_required">
                                Required
                            </label>
                        </div>
                        <button type="button" @click="fields.splice(index, 1)" class="text-xs text-red-600 mt-2">Remove</button>
                    </div>
                </template>
                <div class="flex gap-2 mt-4">
                    <button type="button" @click="addField()" class="tk-btn-secondary text-sm">Add field</button>
                    <button type="submit" class="tk-btn-primary text-sm">Save draft</button>
                </div>
            </form>
        </div>
        <aside class="tk-card p-5 h-fit">
            <h3 class="font-semibold mb-2">Collection point families</h3>
            <p class="text-sm text-tk-muted mb-4">Pick types from the Tilkit field reference (TXT, NUM, CHO, GEO, MED…).</p>
            <template x-for="group in families" :key="group.family">
                <details class="mb-2">
                    <summary class="cursor-pointer text-sm font-medium" x-text="group.family"></summary>
                    <ul class="text-xs text-tk-muted mt-1 space-y-1">
                        <template x-for="t in group.types" :key="t.code">
                            <li><button type="button" class="text-tk-accent hover:underline" @click="addField(t.code)" x-text="t.code"></button></li>
                        </template>
                    </ul>
                </details>
            </template>
        </aside>
    </div>

    @push('head')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formBuilder', (initial, families) => ({
                fields: initial.length ? initial : [],
                families,
                addField(type = 'text') {
                    const n = this.fields.length + 1;
                    this.fields.push({
                        name: 'field_' + n,
                        field_type: type,
                        label: 'Question ' + n,
                        hint: '',
                        is_required: false,
                    });
                },
            }));
        });
    </script>
    @endpush
</x-app-layout>
