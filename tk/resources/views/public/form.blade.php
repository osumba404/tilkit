@php
    use App\Support\Seo\PublicPageSeo;
    $hasGeo = collect($pages)->flatMap(fn ($p) => $p['fields'])->contains(
        fn ($f) => in_array($f->field_type, ['geopoint', 'geotrace', 'geoshape'], true)
    );
    $viteEntries = ['resources/css/app.css', 'resources/js/public-form.js', 'resources/js/public-form-wizard.js'];
    if ($hasGeo) {
        $viteEntries[] = 'resources/js/public-geo.js';
    }
    $pageTitle = PublicPageSeo::pageTitle($form->title);
    $meta = PublicPageSeo::formDescription($form);
@endphp
<x-layouts.public
    :title="$pageTitle"
    :meta-description="$meta"
    :canonical="route('public.forms.show', $form)"
    robots="noindex, nofollow"
    :vite="$viteEntries"
>
    <article aria-labelledby="form-heading">
        @if ($form->project?->public_collection_enabled)
            <nav class="mb-2" aria-label="Breadcrumb">
                <a href="{{ route('public.tools.collect', $form->project) }}" class="tk-link-muted text-tk-accent">← Back to tool</a>
            </nav>
        @endif
        <header>
            <p class="tk-public-eyebrow mt-2">{{ $form->project?->name }}</p>
            <h1 id="form-heading" class="tk-public-title">{{ $form->title }}</h1>
            @if ($form->description)
                <p class="text-tk-muted mt-2 text-sm">{{ $form->description }}</p>
            @endif
        </header>

        @include('public.partials.form-wizard', [
            'pages' => $pages,
            'submitAction' => route('public.forms.submit', $form),
        ])
    </article>
</x-layouts.public>
