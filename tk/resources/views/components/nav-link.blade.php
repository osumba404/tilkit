@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-tk-ink bg-tk-soft ring-1 ring-tk-line/80 shadow-tk-xs transition duration-150'
            : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-tk-muted hover:text-tk-ink hover:bg-tk-soft/70 transition duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
