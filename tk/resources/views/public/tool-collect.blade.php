@php
    $hasGeo = collect($pages)->flatMap(fn ($p) => $p['fields'])->contains(
        fn ($f) => in_array($f->field_type, ['geopoint', 'geotrace', 'geoshape'], true)
    );
    $viteEntries = ['resources/css/app.css', 'resources/js/public-form.js', 'resources/js/public-form-wizard.js'];
    if ($hasGeo) {
        $viteEntries[] = 'resources/js/public-geo.js';
    }
@endphp
<x-layouts.public :title="$tool->name.' - Collect data'" :vite="$viteEntries">
    <x-slot:branding>
        @include('public.partials.branding', ['tool' => $tool])
    </x-slot:branding>

    <p class="tk-public-eyebrow">Tilkit · Public collection</p>
    <h1 class="tk-public-title">{{ $tool->name }}</h1>
    @if ($tool->purpose)
        <p class="text-tk-muted mt-2 text-sm">{{ \Illuminate\Support\Str::limit($tool->purpose, 280) }}</p>
    @endif

    <details class="tk-card p-4 mt-6 text-sm">
        <summary class="cursor-pointer font-medium">Share link & QR code</summary>
        <div class="flex flex-col sm:flex-row gap-4 mt-4 items-center">
            <img src="{{ route('public.tools.qr', $tool) }}" width="140" height="140" alt="QR code" class="rounded-lg border border-tk-line bg-white p-1 shadow-tk-xs">
            <div class="flex-1 w-full">
                <input type="text" readonly value="{{ $publicUrl }}" class="tk-input text-xs font-mono">
            </div>
        </div>
    </details>

    @if ($pages === [])
        <p class="text-sm text-tk-muted mt-8">No published forms yet.</p>
    @else
        @include('public.partials.form-wizard', [
            'pages' => $pages,
            'submitAction' => route('public.tools.submit', $tool),
            'draftSaveUrl' => $draftSaveUrl ?? null,
            'draftPayload' => $draftPayload ?? [],
        ])
    @endif

    <x-slot:footer>
        <footer class="border-t border-tk-line py-6 text-center text-xs text-tk-muted max-w-xl mx-auto px-4 mt-8 space-y-2">
            @if ($tool->brand_footer)
                <p>{{ $tool->brand_footer }}</p>
            @endif
            <p>
                <a href="{{ route('public.tools.privacy', $tool) }}" class="text-tk-accent underline">Privacy notice</a>
                ·
                <a href="{{ route('public.tools.data-subject-request', $tool) }}" class="text-tk-accent underline">Your data rights</a>
            </p>
        </footer>
    </x-slot:footer>
</x-layouts.public>
