@php
    use App\Support\Seo\PublicPageSeo;
    $pageTitle = PublicPageSeo::pageTitle('Thank you - '.$tool->name);
    $meta = 'Your responses were recorded for '.$tool->name.'.';
@endphp
<x-layouts.public
    :title="$pageTitle"
    :meta-description="$meta"
    robots="noindex, nofollow"
    :centered="true"
>
    <article class="tk-card p-8 text-center shadow-tk-md" aria-labelledby="tool-thanks-heading">
        <h1 id="tool-thanks-heading" class="font-display text-2xl font-bold text-tk-ink">Thank you</h1>
        <p class="text-tk-muted mt-3 text-sm" role="status">Your responses for <strong class="text-tk-ink">{{ $tool->name }}</strong> were recorded securely.</p>
        <a href="{{ route('public.tools.collect', $tool) }}" class="tk-btn-primary inline-flex mt-6">Submit another</a>
    </article>
</x-layouts.public>
