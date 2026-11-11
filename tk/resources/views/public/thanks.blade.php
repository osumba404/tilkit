@php
    use App\Support\Seo\PublicPageSeo;
    $pageTitle = PublicPageSeo::pageTitle('Thank you');
@endphp
<x-layouts.public
    :title="$pageTitle"
    meta-description="Your form submission was recorded."
    robots="noindex, nofollow"
    :centered="true"
>
    <article class="tk-card p-8 text-center shadow-tk-md" aria-labelledby="thanks-heading">
        <h1 id="thanks-heading" class="font-display text-2xl font-bold text-tk-ink">Thank you</h1>
        <p class="text-tk-muted mt-3 text-sm" role="status">Your response to <strong class="text-tk-ink">{{ $form->title }}</strong> was recorded securely.</p>
        <nav class="flex flex-col gap-2 mt-6" aria-label="Next steps">
            @if ($form->project?->public_collection_enabled)
                <a href="{{ route('public.tools.collect', $form->project) }}" class="tk-btn-primary">Back to full tool form</a>
            @endif
            <a href="{{ route('home') }}" class="tk-btn-secondary">Back to Tilkit</a>
        </nav>
    </article>
</x-layouts.public>
