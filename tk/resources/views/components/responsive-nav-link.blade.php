@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-2 border-tk-accent text-start text-base font-semibold text-tk-ink bg-tk-soft focus:outline-none transition duration-150'
            : 'block w-full ps-3 pe-4 py-2 border-l-2 border-transparent text-start text-base font-medium text-tk-muted hover:text-tk-ink hover:bg-tk-soft/80 hover:border-tk-line focus:outline-none transition duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
