@props(['title', 'eyebrow' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap justify-between items-start gap-4 animate-tk-fade-in-down']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-medium uppercase tracking-tk-label text-tk-accent mb-1">{{ $eyebrow }}</p>
        @endif
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-tk-ink leading-tight">{{ $title }}</h1>
        @if ($subtitle)
            <p class="tk-page-lead max-w-3xl">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2 shrink-0 items-center">
            {{ $actions }}
        </div>
    @endisset
</div>
