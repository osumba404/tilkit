<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tool->name }} — Collect data</title>
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
    <p class="text-xs text-tk-muted uppercase tracking-wide">Tilkit · Public collection</p>
    <h1 class="text-3xl font-semibold mt-1">{{ $tool->name }}</h1>
    @if ($tool->purpose)
        <p class="text-tk-muted mt-2 text-sm">{{ \Illuminate\Support\Str::limit($tool->purpose, 280) }}</p>
    @endif

    <details class="tk-card p-4 mt-6 text-sm">
        <summary class="cursor-pointer font-medium">Share link & QR code</summary>
        <div class="flex flex-col sm:flex-row gap-4 mt-4 items-center">
            <img src="{{ route('public.tools.qr', $tool) }}" width="140" height="140" alt="QR code" class="rounded border border-tk-line bg-white p-1">
            <div class="flex-1 w-full">
                <input type="text" readonly value="{{ $publicUrl }}" class="w-full rounded-md border border-tk-line px-2 py-1.5 text-xs font-mono bg-tk-soft">
            </div>
        </div>
    </details>

    @if ($pages === [])
        <p class="text-sm text-tk-muted mt-8">No published forms yet.</p>
    @else
        @include('public.partials.form-wizard', [
            'pages' => $pages,
            'submitAction' => route('public.tools.submit', $tool),
        ])
    @endif
</div>
</body>
</html>
