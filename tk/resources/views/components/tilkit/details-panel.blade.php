@props([
    'open' => false,
    'variant' => 'default',
    'innerClass' => '',
])

@php
    $panelClass = 'tk-details-panel'.($variant === 'card' ? ' tk-details-panel--card mb-3' : '');
@endphp

<details {{ $attributes->class($panelClass) }} @if($open) open @endif>
    <summary>{{ $summary }}</summary>
    <div class="tk-details-panel-body">
        <div @class(array_filter(['tk-details-panel-inner', $innerClass]))>
            {{ $slot }}
        </div>
    </div>
</details>
