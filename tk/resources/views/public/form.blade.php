<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }} — Tilkit</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans bg-tk-bg text-tk-ink min-h-screen">
<div class="max-w-xl mx-auto px-4 py-10">
    <p class="text-xs text-tk-muted uppercase tracking-wide">{{ $form->project?->name }}</p>
    <h1 class="font-serif text-3xl font-bold mt-1">{{ $form->title }}</h1>
    @if ($form->description)
        <p class="text-tk-muted mt-2">{{ $form->description }}</p>
    @endif

    <form method="POST" action="{{ route('public.forms.submit', $form) }}" class="tk-card p-6 mt-8 space-y-5">
        @csrf
        @foreach ($form->currentVersion?->fields ?? [] as $field)
            <div>
                <label class="block text-sm font-medium mb-1">
                    {{ $field->label ?? $field->name }}
                    @if ($field->is_required)<span class="text-red-600">*</span>@endif
                </label>
                @if ($field->hint)<p class="text-xs text-tk-muted mb-1">{{ $field->hint }}</p>@endif
                @if (in_array($field->field_type, ['integer', 'decimal']))
                    <input type="number" name="{{ $field->name }}" @required($field->is_required) class="w-full rounded-md border border-tk-line px-3 py-2">
                @elseif ($field->field_type === 'date')
                    <input type="date" name="{{ $field->name }}" @required($field->is_required) class="w-full rounded-md border border-tk-line px-3 py-2">
                @elseif ($field->field_type === 'note')
                    <p class="text-sm text-tk-muted italic">{{ $field->label }}</p>
                @else
                    <input type="text" name="{{ $field->name }}" @required($field->is_required) class="w-full rounded-md border border-tk-line px-3 py-2">
                @endif
                @error($field->name)<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <button type="submit" class="tk-btn-primary w-full">Submit</button>
    </form>
</div>
</body>
</html>
