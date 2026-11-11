@php
    use App\Support\Seo\PublicPageSeo;
    $meta = PublicPageSeo::toolDescription($tool);
    $pageTitle = PublicPageSeo::pageTitle('Privacy notice - '.$tool->name);
    $canonical = route('public.tools.privacy', $tool);
    $jsonLd = PublicPageSeo::webPageJsonLd('Privacy notice - '.$tool->name, $canonical, $meta);
@endphp

<x-layouts.public
    :title="$pageTitle"
    :meta-description="$meta"
    :canonical="$canonical"
    robots="index, follow"
    :json-ld="$jsonLd"
    :narrow="false"
>
    <article aria-labelledby="privacy-heading">
        <header class="mb-6">
            <p class="text-xs font-medium uppercase tracking-wide text-tk-muted">Kenya Data Protection Act</p>
            <h1 id="privacy-heading" class="text-2xl font-semibold text-tk-ink mt-1">Privacy notice</h1>
            <p class="text-sm text-tk-muted mt-2">{{ $tool->name }}</p>
        </header>

        @if (session('status'))
            <div class="tk-callout mb-6 text-sm" role="status">{{ session('status') }}</div>
        @endif

        <section class="tk-card p-6 mb-8" aria-labelledby="privacy-summary-heading">
            <h2 id="privacy-summary-heading" class="sr-only">Summary</h2>
            <div class="prose prose-sm max-w-none text-tk-ink whitespace-pre-wrap">{{ $notice }}</div>
        </section>

        @if ($inventory !== [])
            <section class="tk-card p-6 mb-8" aria-labelledby="privacy-inventory-heading">
                <h2 id="privacy-inventory-heading" class="font-semibold text-lg mb-3">Personal data we collect in the indicator workbook</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">Workbook columns that may contain personal data</caption>
                        <thead class="text-left text-xs uppercase text-tk-muted">
                            <tr>
                                <th scope="col" class="pb-2">Column</th>
                                <th scope="col" class="pb-2">Category</th>
                                <th scope="col" class="pb-2">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inventory as $letter => $meta)
                                <tr class="border-t border-tk-line">
                                    <th scope="row" class="py-2 font-mono font-normal">{{ $letter }}</th>
                                    <td class="py-2">{{ $meta['category'] ?? '' }}</td>
                                    <td class="py-2">{{ $meta['description'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <nav class="flex flex-wrap gap-3" aria-label="Privacy actions">
            <a href="{{ route('public.tools.data-subject-request', $tool) }}" class="tk-btn-primary text-sm">Exercise your rights</a>
            @if ($tool->privacy_policy_url)
                <a href="{{ $tool->privacy_policy_url }}" class="tk-btn-secondary text-sm" target="_blank" rel="noopener noreferrer">Full privacy policy</a>
            @endif
        </nav>
    </article>
</x-layouts.public>
