@php
    use App\Support\Seo\PublicPageSeo;
    $pageTitle = PublicPageSeo::pageTitle('Data subject request - '.$tool->name);
    $meta = 'Submit an access, correction, erasure, or objection request under the Kenya Data Protection Act for '.$tool->name.'.';
@endphp

<x-layouts.public
    :title="$pageTitle"
    :meta-description="$meta"
    :canonical="route('public.tools.data-subject-request', $tool)"
    robots="noindex, follow"
>
    <article aria-labelledby="dsr-heading">
        <header class="mb-6">
            <p class="text-xs font-medium uppercase tracking-wide text-tk-muted">Kenya DPA · Section 26</p>
            <h1 id="dsr-heading" class="text-2xl font-semibold text-tk-ink mt-1">Data subject request</h1>
            <p class="text-sm text-tk-muted mt-2">{{ $tool->name }}</p>
        </header>

        <form method="POST" action="{{ route('public.tools.data-subject-request.store', $tool) }}" class="tk-card p-6 space-y-4" aria-describedby="dsr-help">
            @csrf
            <div>
                <label class="text-sm font-medium" for="request_type">Request type</label>
                <select id="request_type" name="request_type" required class="tk-input w-full mt-1">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium" for="requester_name">Your name</label>
                <input id="requester_name" type="text" name="requester_name" required class="tk-input w-full mt-1" value="{{ old('requester_name') }}" autocomplete="name">
            </div>
            <div>
                <label class="text-sm font-medium" for="requester_email">Email</label>
                <input id="requester_email" type="email" name="requester_email" required class="tk-input w-full mt-1" value="{{ old('requester_email') }}" autocomplete="email">
            </div>
            <div>
                <label class="text-sm font-medium" for="requester_phone">Phone <span class="text-tk-muted">(optional)</span></label>
                <input id="requester_phone" type="tel" name="requester_phone" class="tk-input w-full mt-1" value="{{ old('requester_phone') }}" autocomplete="tel">
            </div>
            <div>
                <label class="text-sm font-medium" for="record_reference">Indicator row ID <span class="text-tk-muted">(optional)</span></label>
                <input id="record_reference" type="text" name="record_reference" class="tk-input w-full mt-1" placeholder="e.g. KEN-01-ANI-CRO-001" value="{{ old('record_reference') }}">
            </div>
            <div>
                <label class="text-sm font-medium" for="details">Details</label>
                <textarea id="details" name="details" rows="4" class="tk-input w-full mt-1">{{ old('details') }}</textarea>
            </div>
            <button type="submit" class="tk-btn-primary w-full">Submit request</button>
            <p id="dsr-help" class="text-xs text-tk-muted">The data controller will respond within 30 days where practicable.</p>
        </form>
        <p class="mt-4 text-sm">
            <a href="{{ route('public.tools.privacy', $tool) }}" class="text-tk-accent underline">Back to privacy notice</a>
        </p>
    </article>
</x-layouts.public>
