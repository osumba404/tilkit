@php
    use App\Support\FieldTypeHelper;
    $params = $field->parameters ?? [];
    $choices = $params['choices'] ?? [];
    $type = $field->field_type;
    $name = $field->name;
@endphp

<div class="tk-field" data-field-type="{{ $type }}">
    @unless ($type === 'note')
        <label class="block text-sm font-medium mb-1" for="field-{{ $name }}">
            {{ $field->label ?? FieldTypeHelper::label($name) }}
            @if ($field->is_required)<span class="text-red-600" aria-hidden="true">*</span>@endif
        </label>
    @endunless
    @if ($field->hint)
        <p class="text-xs text-tk-muted mb-2">{{ $field->hint }}</p>
    @endif

    @switch($type)
        @case('note')
            <p class="text-sm text-tk-muted bg-tk-soft rounded-md px-3 py-2">{{ $field->label }}</p>
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

        @case('integer')
        @case('decimal')
            <input id="field-{{ $name }}" type="number" name="{{ $name }}" step="{{ $type === 'integer' ? '1' : 'any' }}"
                @if(isset($params['min'])) min="{{ $params['min'] }}" @endif
                @if(isset($params['max'])) max="{{ $params['max'] }}" @endif
                value="{{ old($name) }}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
            @break

        @case('range')
            <input id="field-{{ $name }}" type="range" name="{{ $name }}"
                @if(isset($params['min'])) min="{{ $params['min'] ?? 0 }}" @else min="0" @endif
                @if(isset($params['max'])) max="{{ $params['max'] ?? 100 }}" @else max="100" @endif
                value="{{ old($name, $params['min'] ?? 0) }}" @required($field->is_required)
                class="w-full accent-tk-accent">
            @break

        @case('date')
            <input id="field-{{ $name }}" type="date" name="{{ $name }}" value="{{ old($name) }}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
            @break

        @case('time')
            <input id="field-{{ $name }}" type="time" name="{{ $name }}" value="{{ old($name) }}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
            @break

        @case('datetime')
            <input id="field-{{ $name }}" type="datetime-local" name="{{ $name }}" value="{{ old($name) }}" @required($field->is_required)
                class="w-full rounded-md border border-tk-line px-3 py-2">
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
        @case('signature')
            <input id="field-{{ $name }}" type="file" name="{{ $name }}"
                @required($field->is_required)
                class="w-full text-sm file:mr-3 file:tk-btn-secondary file:border-0 file:text-sm">
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
</div>
