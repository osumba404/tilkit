@props(['id', 'title', 'num' => null, 'lead' => null, 'search' => ''])

<section
    id="{{ $id }}"
    data-search="{{ $search }}"
    {{ $attributes->merge(['class' => 'tk-guide-article tk-guide-section-item']) }}
    x-show="sectionVisible($el.dataset.search || '')"
>
    <h3 class="tk-guide-article-title">
        @if ($num)
            <span class="tk-guide-article-num text-tk-accent">{{ $num }}</span>
        @endif
        {{ $title }}
    </h3>
    @if ($lead)
        <p class="tk-guide-lead max-w-3xl">{{ $lead }}</p>
    @endif
    <div class="tk-guide-body max-w-3xl">
        {{ $slot }}
    </div>
</section>
