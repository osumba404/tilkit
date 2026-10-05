<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }} - Tilkit</title>
    @php
        $hasGeo = collect($pages)->flatMap(fn ($p) => $p['fields'])->contains(
            fn ($f) => in_array($f->field_type, ['geopoint', 'geotrace', 'geoshape'], true)
        );
        $viteEntries = ['resources/css/app.css', 'resources/js/public-form.js', 'resources/js/public-form-wizard.js'];
        if ($hasGeo) {
            $viteEntries[] = 'resources/js/public-geo.js';
        }
    @endphp
    @vite($viteEntries)
</head>
<body class="font-body bg-tk-bg text-tk-ink min-h-screen">
<div class="max-w-xl mx-auto px-4 py-10">
    @if ($form->project?->public_collection_enabled)
        <a href="{{ route('public.tools.collect', $form->project) }}" class="text-sm text-tk-accent">← Back to tool</a>
    @endif
    <p class="text-xs text-tk-muted uppercase tracking-wide mt-2">{{ $form->project?->name }}</p>
    <h1 class="text-3xl font-semibold mt-1">{{ $form->title }}</h1>
    @if ($form->description)
        <p class="text-tk-muted mt-2">{{ $form->description }}</p>
    @endif

    @include('public.partials.form-wizard', [
        'pages' => $pages,
        'submitAction' => route('public.forms.submit', $form),
    ])
</div>
</body>
</html>
