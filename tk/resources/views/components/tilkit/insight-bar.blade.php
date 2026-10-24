@props([
    'label',
    'count',
    'max' => 1,
    'suffix' => '',
])

@php
    $pct = min(100, max(0, (int) round(($count / max(1, $max)) * 100)));
@endphp
<li class="space-y-1">
    <div class="flex justify-between gap-3 text-sm">
        <span class="text-tk-muted truncate">{{ $label }}</span>
        <span class="font-medium text-tk-ink tabular-nums shrink-0">{{ number_format($count) }}{{ $suffix }}</span>
    </div>
    <div class="h-2 rounded-full bg-tk-soft overflow-hidden" role="presentation">
        <div class="h-full rounded-full bg-tk-accent transition-all duration-500" style="width: {{ $pct }}%"></div>
    </div>
</li>
