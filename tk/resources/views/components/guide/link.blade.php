<a {{ $attributes->merge(['class' => 'tk-guide-more']) }}>
    {{ $slot }}
    <span class="tk-guide-more-chevron" aria-hidden="true">›</span>
</a>
