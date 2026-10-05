<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }} - Tilkit</title>
    @php
        $hasGeo = ($liveVersion->fields ?? collect())->contains(
            fn ($f) => in_array($f->field_type, ['geopoint', 'geotrace', 'geoshape'], true)
        );
        $viteEntries = ['resources/css/app.css', 'resources/js/public-form.js'];
        if ($hasGeo) {
            $viteEntries[] = 'resources/js/public-geo.js';
        }
    @endphp
    @vite($viteEntries)
</head>
<body class="font-body bg-tk-bg text-tk-ink min-h-screen">
<div class="max-w-xl mx-auto px-4 py-10">
    <p class="text-xs text-tk-muted uppercase tracking-wide">{{ $form->project?->name }}</p>
    <h1 class="text-3xl font-semibold mt-1">{{ $form->title }}</h1>
    @if ($form->description)
        <p class="text-tk-muted mt-2">{{ $form->description }}</p>
    @endif

    <form method="POST" action="{{ route('public.forms.submit', $form) }}" enctype="multipart/form-data" class="tk-card p-6 mt-8 space-y-6">
        @csrf
        @foreach ($liveVersion->fields ?? [] as $index => $field)
            <div class="flex gap-3">
                <span class="text-xs font-medium text-tk-muted pt-2 w-6 shrink-0">{{ $index + 1 }}.</span>
                <div class="flex-1">
                    @include('public.partials.field', ['field' => $field])
                </div>
            </div>
        @endforeach
        <button type="submit" class="tk-btn-primary w-full">Submit</button>
    </form>
</div>
</body>
</html>
