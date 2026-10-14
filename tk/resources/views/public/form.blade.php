@php
    $hasGeo = collect($pages)->flatMap(fn ($p) => $p['fields'])->contains(
        fn ($f) => in_array($f->field_type, ['geopoint', 'geotrace', 'geoshape'], true)
    );
    $viteEntries = ['resources/css/app.css', 'resources/js/public-form.js', 'resources/js/public-form-wizard.js'];
    if ($hasGeo) {
        $viteEntries[] = 'resources/js/public-geo.js';
    }
@endphp
<x-layouts.public :title="$form->title.' — Tilkit'" :vite="$viteEntries">
    @if ($form->project?->public_collection_enabled)
        <a href="{{ route('public.tools.collect', $form->project) }}" class="tk-link-muted text-tk-accent">← Back to tool</a>
    @endif
    <p class="tk-public-eyebrow mt-2">{{ $form->project?->name }}</p>
    <h1 class="tk-public-title">{{ $form->title }}</h1>
    @if ($form->description)
        <p class="text-tk-muted mt-2 text-sm">{{ $form->description }}</p>
    @endif

    @include('public.partials.form-wizard', [
        'pages' => $pages,
        'submitAction' => route('public.forms.submit', $form),
    ])
</x-layouts.public>
