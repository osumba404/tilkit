@php
    use App\Support\Seo\PublicPageSeo;
    $hasGeo = collect($pages)->flatMap(fn ($p) => $p['fields'])->contains(
        fn ($f) => in_array($f->field_type, ['geopoint', 'geotrace', 'geoshape'], true)
    );
    $viteEntries = ['resources/css/app.css', 'resources/js/public-form.js', 'resources/js/public-form-wizard.js'];
    if ($hasGeo) {
        $viteEntries[] = 'resources/js/public-geo.js';
    }
    $pageTitle = PublicPageSeo::pageTitle($tool->name.' - Collect data');
    $meta = PublicPageSeo::toolDescription($tool);
@endphp
<x-layouts.public
    :title="$pageTitle"
    :meta-description="$meta"
    :canonical="route('public.tools.collect', $tool)"
    robots="noindex, nofollow"
    :vite="$viteEntries"
>
    <x-slot:branding>
        @include('public.partials.branding', ['tool' => $tool])
    </x-slot:branding>

    <article aria-labelledby="collect-heading">
        <header>
            <p class="tk-public-eyebrow">Tilkit · Public collection</p>
            <h1 id="collect-heading" class="tk-public-title">{{ $tool->name }}</h1>
            @if ($tool->purpose)
                <p class="text-tk-muted mt-2 text-sm">{{ \Illuminate\Support\Str::limit($tool->purpose, 280) }}</p>
            @endif
        </header>

        <details class="tk-card p-4 mt-6 text-sm">
            <summary class="cursor-pointer font-medium">Share link &amp; QR code</summary>
            <div class="flex flex-col sm:flex-row gap-4 mt-4 items-center">
                <img src="{{ route('public.tools.qr', $tool) }}" width="140" height="140" alt="QR code linking to this collection page" class="rounded-lg border border-tk-line bg-white p-1 shadow-tk-xs" loading="lazy" decoding="async">
                <div class="flex-1 w-full">
                    <label class="sr-only" for="public-collect-url">Collection page URL</label>
                    <input id="public-collect-url" type="text" readonly value="{{ $publicUrl }}" class="tk-input text-xs font-mono">
                </div>
            </div>
        </details>

        @if ($pages === [])
            <p class="text-sm text-tk-muted mt-8" role="status">No published forms yet.</p>
        @else
            @include('public.partials.form-wizard', [
                'pages' => $pages,
                'submitAction' => route('public.tools.submit', $tool),
                'draftSaveUrl' => $draftSaveUrl ?? null,
                'draftPayload' => $draftPayload ?? [],
            ])
        @endif
    </article>

    <x-slot:footer>
        <footer class="border-t border-tk-line py-6 text-center text-xs text-tk-muted max-w-xl mx-auto px-4 mt-8 space-y-2" role="contentinfo">
            @if ($tool->brand_footer)
                <p>{{ $tool->brand_footer }}</p>
            @endif
            <p>
                <a href="{{ route('public.tools.privacy', $tool) }}" class="text-tk-accent underline">Privacy notice</a>
                <span aria-hidden="true"> · </span>
                <a href="{{ route('public.tools.data-subject-request', $tool) }}" class="text-tk-accent underline">Your data rights</a>
            </p>
        </footer>
    </x-slot:footer>
</x-layouts.public>
