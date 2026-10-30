@props(['letter', 'title' => null])

@php
    $letter = strtoupper((string) $letter);
    $titleText = $title ?? \App\Support\Tilkit\WorkbookFormLabel::titleForLetter($letter);
@endphp
<div {{ $attributes->merge(['class' => 'mb-1']) }}>
    <span class="text-sm text-tk-ink font-medium leading-snug block">{{ $titleText }}</span>
    <span class="text-xs font-mono text-tk-accent">{{ \App\Support\Tilkit\WorkbookFormLabel::columnReference($letter) }}</span>
</div>
