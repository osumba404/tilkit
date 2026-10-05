@php
    use App\Support\DateTimeFieldValidator;
    use App\Support\FieldTypeHelper;
    $params = $field->parameters ?? [];
    $choices = $params['choices'] ?? [];
    $type = $field->field_type;
    $name = $field->name;
    $typeLabel = FieldTypeHelper::label($type);
@endphp

<div class="tk-field" data-field-type="{{ $type }}">
    @unless (in_array($type, ['note', 'group', 'repeat', 'acknowledge'], true))
        <div class="mb-1">
            <label class="block text-sm font-medium" for="field-{{ $name }}">
                {{ $field->label ?: $typeLabel }}
                @if ($field->is_required)<span class="text-red-600" aria-hidden="true">*</span>@endif
            </label>
            <p class="text-xs text-tk-muted mt-0.5">
                <span>{{ $typeLabel }}</span>
                <span class="font-mono">{{ $type }}</span>
            </p>
        </div>
    @endunless
    @if ($field->hint && ! in_array($type, ['note'], true))
        <p class="text-xs text-tk-muted mb-2">{{ $field->hint }}</p>
    @endif

    @switch($type)
        @case('note')
            <p class="text-sm text-tk-muted bg-tk-soft rounded-md px-3 py-2">
                <span class="font-medium text-tk-ink">{{ $field->label }}</span>
                <span class="block text-xs mt-1">{{ $typeLabel }} <span class="font-mono">{{ $type }}</span></span>
            </p>
            @break

        @case('group')
        @case('repeat')
            <div class="border-l-4 border-tk-accent/80 pl-3 py-2 bg-tk-soft/50 rounded-r-md">
                <p class="text-sm font-semibold">{{ $field->label ?: FieldTypeHelper::label($type) }}</p>
                <p class="text-xs text-tk-muted mt-1">{{ $typeLabel }} <span class="font-mono">{{ $type }}</span> — section heading for grouped questions.</p>
            </div>
            @break

        @case('select_one')
            @if (count($choices))
                <select id="field-{{ $name }}" name="{{ $name }}" @required($field->is_required)
                    class="w-full rounded-md border border-tk-line px-3 py-2 bg-white">
                    <option value="">Choose one</option>
                    @foreach ($choices as $choice)
                        <option value="{{ $choice['value'] ?? $choice['label'] }}" @selected(old($name) == ($choice['value'] ?? $choice['label']))>
                            {{ $choice['label'] ?? $choice['value'] }}
                        </option>
                    @endforeach
                </select>
            @else
                <p class="text-xs text-amber-700">No choices configured for this field.</p>
            @endif
            @break

        @case('select_multiple')
            @php $max = (int) ($params['max_selections'] ?? 0); @endphp
            @if (count($choices))
                <div id="field-{{ $name }}" data-multi-select data-max="{{ $max }}" class="space-y-2 rounded-md border border-tk-line p-3 bg-white">
                    @foreach ($choices as $choice)
                        @php $val = $choice['value'] ?? $choice['label']; @endphp
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="{{ $name }}[]" value="{{ $val }}"
                                @checked(is_array(old($name)) && in_array($val, old($name), true))
                                class="rounded border-tk-line text-tk-accent focus:ring-tk-accent">
                            <span>{{ $choice['label'] ?? $val }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($max > 0)
                    <p class="text-xs text-tk-muted mt-1">Select up to {{ $max }} options.</p>
                    <p class="text-xs text-amber-700 mt-1" data-multi-limit-msg hidden></p>
                @endif
            @else
                <p class="text-xs text-amber-700">No choices configured for this field.</p>
            @endif
            @break

        @case('rank')
            @if (count($choices))
                @php
                    $oldRank = old($name);
                    $ordered = $choices;
                    if (is_string($oldRank)) {
                        $decoded = json_decode($oldRank, true);
                        if (is_array($decoded)) {
                            $byVal = collect($choices)->keyBy(fn ($c) => $c['value'] ?? $c['label']);
                            $ordered = [];
                            foreach ($decoded as $v) {
                                if ($byVal->has($v)) {
                                    $ordered[] = $byVal->get($v);
                                }
                            }
                            foreach ($choices as $c) {
                                $cv = $c['value'] ?? $c['label'];
                                if (! in_array($cv, $decoded, true)) {
                                    $ordered[] = $c;
                                }
                            }
                        }
                    }
                @endphp
                <p class="text-xs text-tk-muted mb-2">Drag items to rank (best at top).</p>
                <ul
                    id="field-{{ $name }}"
                    data-rank-list
                    data-rank-name="{{ $name }}"
                    class="space-y-1 rounded-md border border-tk-line p-2 bg-white"
                >
                    @foreach ($ordered as $choice)
                        @php $val = $choice['value'] ?? $choice['label']; @endphp
                        <li
                            class="flex items-center gap-2 rounded border border-tk-line/60 bg-tk-soft/30 px-2 py-2 text-sm cursor-grab active:cursor-grabbing"
                            data-rank-value="{{ $val }}"
                        >
                            <span class="text-tk-muted drag-handle" aria-hidden="true">⋮⋮</span>
                            <span class="flex-1">{{ $choice['label'] ?? $val }}</span>
                        </li>
                    @endforeach
                </ul>
                <input type="hidden" name="{{ $name }}" id="field-{{ $name }}" value="{{ old($name) }}" data-rank-input @required($field->is_required)>
            @else
                <p class="text-xs text-amber-700">No choices configured for rank field.</p>
            @endif
            @break

        @case('integer')
        @case('decimal')
            <input id="field-{{ $name }}" type="number" name="{{ $name }}" step="{{ $type === 'integer' ? '1' : 'any' }}"
                @if(isset($params['min'])) min="{{ $params['min'] }}" @endif
                @if(isset($params['max'])) max="{{ $params['max'] }}" @endif
                value="{{ old($name) }}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2"
                @if($type === 'integer' || $type === 'decimal') data-calc-source @endif>
            @break

        @case('range')
            <div class="flex items-center gap-3">
                <input id="field-{{ $name }}" type="range" name="{{ $name }}"
                    @if(isset($params['min'])) min="{{ $params['min'] ?? 0 }}" @else min="0" @endif
                    @if(isset($params['max'])) max="{{ $params['max'] ?? 100 }}" @else max="100" @endif
                    value="{{ old($name, $params['min'] ?? 0) }}" @required($field->is_required)
                    class="flex-1 accent-tk-accent" data-range-output="{{ $name }}"
                    data-calc-source>
                <output for="field-{{ $name }}" class="text-sm font-mono w-10 text-right" data-range-display="{{ $name }}">{{ old($name, $params['min'] ?? 0) }}</output>
            </div>
            @break

        @case('date')
            @php
                $dateVal = old($name);
                if (is_string($dateVal) && $dateVal !== '') {
                    try {
                        $dateVal = DateTimeFieldValidator::forHtmlInput('date', DateTimeFieldValidator::parseAndValidate('date', $dateVal, $name));
                    } catch (\Throwable) {
                        /* keep raw old input */
                    }
                }
            @endphp
            <input id="field-{{ $name }}" type="date" name="{{ $name }}" value="{{ $dateVal }}"
                pattern="\d{4}-\d{2}-\d{2}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
            <p class="text-xs text-tk-muted mt-1">Format: {{ DateTimeFieldValidator::formatHint('date') }}</p>
            @break

        @case('time')
            @php
                $timeVal = old($name);
                if (is_string($timeVal) && $timeVal !== '') {
                    try {
                        $timeVal = DateTimeFieldValidator::forHtmlInput('time', DateTimeFieldValidator::parseAndValidate('time', $timeVal, $name));
                    } catch (\Throwable) {
                        /* keep raw old input */
                    }
                }
            @endphp
            <input id="field-{{ $name }}" type="time" name="{{ $name }}" value="{{ $timeVal }}"
                step="1" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
            <p class="text-xs text-tk-muted mt-1">Format: {{ DateTimeFieldValidator::formatHint('time') }} (24-hour)</p>
            @break

        @case('datetime')
            @php
                $dtVal = old($name);
                if (is_string($dtVal) && $dtVal !== '') {
                    try {
                        $dtVal = DateTimeFieldValidator::forHtmlInput('datetime', DateTimeFieldValidator::parseAndValidate('datetime', $dtVal, $name));
                    } catch (\Throwable) {
                        /* keep raw old input */
                    }
                }
            @endphp
            <input id="field-{{ $name }}" type="datetime-local" name="{{ $name }}" value="{{ $dtVal }}"
                step="1" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
            <p class="text-xs text-tk-muted mt-1">Format: {{ DateTimeFieldValidator::formatHint('datetime') }} (local time)</p>
            @break

        @case('text')
            <input id="field-{{ $name }}" type="text" name="{{ $name }}" value="{{ old($name) }}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2" data-calc-source>
            @break

        @case('barcode')
            <div data-barcode-field class="space-y-2">
                <input id="field-{{ $name }}" type="text" name="{{ $name }}" value="{{ old($name) }}" @required($field->is_required)
                    autocomplete="off" inputmode="numeric"
                    class="w-full rounded-md border border-tk-line px-3 py-2 font-mono"
                    placeholder="Enter or scan barcode">
                <button type="button" class="tk-btn-secondary text-xs py-1.5" data-barcode-scan>Scan with camera</button>
                <p class="text-xs text-tk-muted" data-barcode-status hidden></p>
            </div>
            @break

        @case('acknowledge')
            <p class="text-xs text-tk-muted mb-2">{{ $typeLabel }} <span class="font-mono">{{ $type }}</span></p>
            <label class="flex items-start gap-2 text-sm cursor-pointer rounded-md border border-tk-line p-3 bg-white">
                <input type="checkbox" id="field-{{ $name }}" name="{{ $name }}" value="1"
                    @checked(old($name))
                    @required($field->is_required)
                    class="mt-0.5 rounded border-tk-line text-tk-accent focus:ring-tk-accent">
                <span>{{ $field->label ?: 'I acknowledge' }}</span>
            </label>
            @break

        @case('calculate')
            <input
                id="field-{{ $name }}"
                type="text"
                name="{{ $name }}"
                readonly
                value="{{ old($name) }}"
                data-calculate-output
                data-calculate-expr="{{ $field->calculation_expr ?? ($params['expression'] ?? '') }}"
                class="w-full rounded-md border border-tk-line px-3 py-2 bg-tk-soft text-tk-muted"
            >
            <p class="text-xs text-tk-muted mt-1">Updates when related numeric answers change.</p>
            @break

        @case('metadata')
            <input type="hidden" name="{{ $name }}" id="field-{{ $name }}" value="{{ old($name) }}" data-metadata-field>
            <p class="text-xs text-tk-muted">Submission context (browser, time zone) is attached automatically.</p>
            @break

        @case('image')
            <input id="field-{{ $name }}" type="file" name="{{ $name }}" accept="image/*"
                @if($params['allow_capture'] ?? true) capture="environment" @endif
                @required($field->is_required)
                class="w-full text-sm file:mr-3 file:tk-btn-secondary file:border-0 file:text-sm">
            <p class="text-xs text-tk-muted mt-1">Upload or capture a photo.</p>
            @break

        @case('video')
            <input id="field-{{ $name }}" type="file" name="{{ $name }}" accept="video/*"
                @if($params['allow_capture'] ?? true) capture @endif
                @required($field->is_required)
                class="w-full text-sm file:mr-3 file:tk-btn-secondary file:border-0 file:text-sm">
            <p class="text-xs text-tk-muted mt-1">Upload or record video.</p>
            @break

        @case('audio')
            <input id="field-{{ $name }}" type="file" name="{{ $name }}" accept="audio/*"
                @if($params['allow_capture'] ?? true) capture @endif
                @required($field->is_required)
                class="w-full text-sm file:mr-3 file:tk-btn-secondary file:border-0 file:text-sm">
            <p class="text-xs text-tk-muted mt-1">Upload or record audio.</p>
            @break

        @case('file')
            <input id="field-{{ $name }}" type="file" name="{{ $name }}"
                @required($field->is_required)
                class="w-full text-sm file:mr-3 file:tk-btn-secondary file:border-0 file:text-sm">
            <p class="text-xs text-tk-muted mt-1">Upload a document or file.</p>
            @break

        @case('signature')
            @include('public.partials.field-signature', ['field' => $field, 'name' => $name])
            @break

        @case('geopoint')
        @case('geotrace')
        @case('geoshape')
            @include('public.partials.field-geo', ['field' => $field, 'type' => $type, 'name' => $name])
            @break

        @default
            <input id="field-{{ $name }}" type="text" name="{{ $name }}" value="{{ old($name) }}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
    @endswitch

    @error($name)
        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
    @enderror
    @error($name.'.*')
        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
    @enderror
    @error($name.'_drawn')
        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
