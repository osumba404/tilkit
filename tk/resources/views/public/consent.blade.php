@php
    use App\Support\Seo\PublicPageSeo;
    $pageTitle = PublicPageSeo::pageTitle(__('tk.consent_title', ['tool' => $tool->name]));
    $meta = 'Review and accept consent before submitting data to '.$tool->name.'.';
@endphp
<x-layouts.public
    :title="$pageTitle"
    :meta-description="$meta"
    :canonical="route('public.tools.consent', $tool)"
    robots="noindex, nofollow"
    :vite="['resources/css/app.css', 'resources/js/public-signature.js']"
>
    <x-slot:branding>
        @include('public.partials.branding', ['tool' => $tool])
    </x-slot:branding>

    <article aria-labelledby="consent-heading">
        <header>
            <h1 id="consent-heading" class="font-display text-2xl font-bold text-tk-ink">{{ __('tk.consent_heading') }}</h1>
        </header>
        <section class="tk-card p-6 mt-6 prose prose-sm max-w-none text-tk-ink" aria-labelledby="consent-heading">
            {!! nl2br(e($tool->consent_text)) !!}
        </section>

        <form method="POST" action="{{ route('public.tools.consent.accept', $tool) }}" class="tk-card p-6 mt-6 space-y-4" aria-label="Consent acknowledgement">
            @csrf
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="consent_ack" value="1" required class="tk-checkbox mt-1">
                <span>{{ $tool->consent_ack_label ?: __('tk.consent_ack_default') }}</span>
            </label>

            @if ($tool->consent_require_signature)
                <div>
                    <label class="block text-sm font-medium mb-2" for="consent_signature_field">{{ __('tk.consent_signature') }}</label>
                    @include('public.partials.field', ['field' => (object) [
                        'field_type' => 'signature',
                        'name' => 'consent_signature',
                        'label' => __('tk.consent_signature'),
                        'hint' => null,
                        'is_required' => true,
                        'parameters' => ['allow_draw' => true, 'allow_upload' => true],
                    ]])
                </div>
            @endif

            <button type="submit" class="tk-btn-primary">{{ __('tk.continue_to_form') }}</button>
        </form>

        <nav class="text-center text-xs text-tk-muted mt-6" aria-label="Privacy links">
            <a href="{{ route('public.tools.privacy', $tool) }}" class="text-tk-accent underline">Privacy notice</a>
            <span aria-hidden="true"> · </span>
            <a href="{{ route('public.tools.data-subject-request', $tool) }}" class="text-tk-accent underline">Exercise your rights</a>
        </nav>
    </article>
</x-layouts.public>
