@props(['title'])

<div class="mb-6">
    <h1 class="font-display text-xl font-bold text-tk-ink">{{ $title }}</h1>
    @if ($slot->isNotEmpty())
        <p class="text-sm text-tk-muted mt-1">{{ $slot }}</p>
    @endif
</div>
