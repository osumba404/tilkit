@php
    use App\Support\FieldTypeHelper;
    $initialFields = $form->currentVersion?->fields->map(fn ($f) => [
        'name' => $f->name,
        'field_type' => $f->field_type,
        'label' => $f->label,
        'hint' => $f->hint,
        'is_required' => $f->is_required,
        'parameters' => $f->parameters ?? [],
    ]) ?? collect();
    $typeCatalog = $fieldTypes->map(fn ($t) => [
        'code' => $t->code,
        'label' => FieldTypeHelper::label($t->code),
        'icon' => FieldTypeHelper::icons()[$t->code] ?? 'M4 12h16',
    ])->values();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <h2 class="text-2xl font-semibold">Form builder - {{ $form->title }}</h2>
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

    <div
        x-data="formBuilder(@js($initialFields), @js($typeCatalog))"
        @click.outside="typeMenuOpen = null"
        class="grid lg:grid-cols-3 gap-6"
    >
        <div class="lg:col-span-2 tk-card p-5">
            <form method="POST" action="{{ route('tools.forms.builder.save', [$tool, $form]) }}">
                @csrf
                <div x-ref="fieldList" class="space-y-3">
                    <template x-for="(field, index) in fields" :key="field._key">
                        <div class="border border-tk-line rounded-md p-4 bg-white" :data-field-key="field._key">
                            <div class="flex items-start gap-3 mb-3">
                                <button type="button" class="drag-handle mt-1 cursor-grab text-tk-muted hover:text-tk-ink" title="Drag to reorder" aria-label="Drag to reorder">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 11a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 18a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2z"/></svg>
                                </button>
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-tk-accent-soft text-xs font-semibold text-tk-accent" x-text="index + 1"></span>
                                <div class="flex-1 grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-tk-muted">Field name (key)</label>
                                        <input :name="'fields['+index+'][name]'" x-model="field.name" required pattern="[a-z][a-z0-9_]*" class="w-full rounded border border-tk-line px-2 py-1.5 text-sm">
                                    </div>
                                    <div class="relative">
                                        <label class="text-xs text-tk-muted">Type</label>
                                        <button type="button" @click="toggleTypeMenu(index)" class="w-full flex items-center gap-2 rounded border border-tk-line px-2 py-1.5 text-sm bg-white text-left">
                                            <svg class="h-4 w-4 shrink-0 text-tk-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path :d="typeMeta(field.field_type).icon"/></svg>
                                            <span class="flex-1 truncate" x-text="typeMeta(field.field_type).label"></span>
                                            <span class="font-mono text-xs text-tk-muted" x-text="field.field_type"></span>
                                        </button>
                                        <input type="hidden" :name="'fields['+index+'][field_type]'" :value="field.field_type">
                                        <div x-show="typeMenuOpen === index" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border border-tk-line bg-white shadow-lg">
                                            <template x-for="t in typeCatalog" :key="t.code">
                                                <button type="button" @click="setType(field, t.code)" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-tk-soft text-left">
                                                    <svg class="h-4 w-4 text-tk-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path :d="t.icon"/></svg>
                                                    <span class="flex-1" x-text="t.label"></span>
                                                    <span class="font-mono text-xs text-tk-muted" x-text="t.code"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs text-tk-muted">Label</label>
                                        <input :name="'fields['+index+'][label]'" x-model="field.label" class="w-full rounded border border-tk-line px-2 py-1.5 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs text-tk-muted">Hint</label>
                                        <input :name="'fields['+index+'][hint]'" x-model="field.hint" class="w-full rounded border border-tk-line px-2 py-1.5 text-sm">
                                    </div>
                                    <label class="flex items-center gap-2 text-sm sm:col-span-2">
                                        <input type="checkbox" :name="'fields['+index+'][is_required]'" value="1" x-model="field.is_required">
                                        Required
                                    </label>
                                </div>
                            </div>

                            <div x-show="supportsChoices(field)" class="mt-3 border-t border-tk-line pt-3">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-medium">Answer choices</p>
                                    <button type="button" @click="addChoice(field)" class="text-xs text-tk-accent">Add choice</button>
                                </div>
                                <template x-for="(choice, ci) in field.parameters.choices" :key="ci">
                                    <div class="grid sm:grid-cols-2 gap-2 mb-2">
                                        <input :name="'fields['+index+'][parameters][choices]['+ci+'][value]'" x-model="choice.value" placeholder="Stored value" class="rounded border border-tk-line px-2 py-1 text-sm">
                                        <div class="flex gap-2">
                                            <input :name="'fields['+index+'][parameters][choices]['+ci+'][label]'" x-model="choice.label" placeholder="Label shown to user" class="flex-1 rounded border border-tk-line px-2 py-1 text-sm">
                                            <button type="button" @click="removeChoice(field, ci)" class="text-xs text-red-600 px-2">Remove</button>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="field.field_type === 'select_multiple'" class="mt-2">
                                    <label class="text-xs text-tk-muted">Max selections</label>
                                    <input type="number" min="1" :name="'fields['+index+'][parameters][max_selections]'" x-model.number="field.parameters.max_selections" class="w-24 rounded border border-tk-line px-2 py-1 text-sm">
                                </div>
                            </div>

                            <div x-show="['image','audio','video','file'].includes(field.field_type)" class="mt-3 border-t border-tk-line pt-3 text-sm space-y-2">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" :name="'fields['+index+'][parameters][allow_capture]'" value="1" x-model="field.parameters.allow_capture">
                                    Allow camera / microphone capture
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" :name="'fields['+index+'][parameters][allow_upload]'" value="1" x-model="field.parameters.allow_upload">
                                    Allow file upload
                                </label>
                                <div>
                                    <label class="text-xs text-tk-muted">Max size (MB)</label>
                                    <input type="number" min="1" max="100" :name="'fields['+index+'][parameters][max_size_mb]'" x-model.number="field.parameters.max_size_mb" class="w-24 rounded border border-tk-line px-2 py-1 text-sm">
                                </div>
                            </div>

                            <button type="button" @click="removeField(index)" class="text-xs text-red-600 mt-3">Remove field</button>
                        </div>
                    </template>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" @click="addField()" class="tk-btn-secondary text-sm">Add field</button>
                    <button type="submit" class="tk-btn-primary text-sm">Save draft</button>
                </div>
            </form>
        </div>
        <aside class="tk-card p-5 h-fit">
            <h3 class="font-semibold mb-2">Collection point families</h3>
            <p class="text-sm text-tk-muted mb-4">Add fields from the catalogue. Types show a readable label and code.</p>
            @foreach ($fieldTypes->groupBy('family') as $family => $types)
                <details class="mb-2">
                    <summary class="cursor-pointer text-sm font-medium">{{ $family }}</summary>
                    <ul class="text-xs mt-1 space-y-1">
                        @foreach ($types as $t)
                            <li>
                                <button type="button" @click="addField('{{ $t->code }}')" class="text-tk-accent hover:underline">
                                    {{ FieldTypeHelper::label($t->code) }}
                                    <span class="font-mono text-tk-muted">{{ $t->code }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endforeach
        </aside>
    </div>

</x-app-layout>
