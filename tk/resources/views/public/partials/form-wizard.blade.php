@php
    $totalPages = count($pages);
@endphp
<form
    method="POST"
    action="{{ $submitAction }}"
    enctype="multipart/form-data"
    class="mt-8"
    data-form-wizard
    @if (! empty($draftSaveUrl)) data-draft-save-url="{{ $draftSaveUrl }}" @endif
    @if (! empty($draftPayload)) data-draft-payload="{{ e(json_encode($draftPayload)) }}" @endif
    novalidate
    aria-label="Multi-section form"
>
    @csrf

    @if (session('duplicate_warnings'))
        <div class="tk-card p-4 mb-4 border border-amber-500 bg-amber-50 text-sm" role="alert">
            <p class="font-medium">{{ __('tk.duplicate_warning_title') }}</p>
            <ul class="list-disc ml-5 mt-2">
                @foreach (session('duplicate_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <input type="hidden" name="confirm_duplicate" value="1">
            <p class="text-xs text-tk-muted mt-2">{{ __('tk.submit_anyway') }} — correct any mistakes or submit again.</p>
        </div>
    @endif

    <div class="mb-4 flex items-center justify-between text-xs text-tk-muted">
        <span data-wizard-progress aria-live="polite">Section 1 of {{ $totalPages }}</span>
        <div class="h-1.5 flex-1 mx-4 max-w-xs bg-tk-soft rounded-full overflow-hidden" role="progressbar" aria-valuemin="1" aria-valuemax="{{ $totalPages }}" aria-valuenow="1">
            <div class="h-full bg-tk-accent transition-all duration-300" data-wizard-progress-bar style="width: {{ $totalPages > 0 ? round(100 / $totalPages) : 100 }}%"></div>
        </div>
    </div>

    @foreach ($pages as $pageIndex => $page)
        <section
            class="tk-card p-6 space-y-6"
            data-wizard-page
            data-page-index="{{ $pageIndex }}"
            aria-labelledby="wizard-page-title-{{ $pageIndex }}"
            @if ($pageIndex > 0) hidden @endif
        >
            <header class="border-b border-tk-line pb-4">
                <p class="text-xs font-medium text-tk-accent uppercase tracking-wide">Section {{ $pageIndex + 1 }}</p>
                <h2 id="wizard-page-title-{{ $pageIndex }}" class="text-xl font-semibold mt-1">{{ $page['title'] }}</h2>
                @if (! empty($page['description']))
                    <p class="text-sm text-tk-muted mt-2">{{ $page['description'] }}</p>
                @endif
            </header>

            @foreach ($page['fields'] as $index => $field)
                <div class="flex gap-3">
                    <span class="text-xs font-medium text-tk-muted pt-2 w-6 shrink-0">{{ $index + 1 }}.</span>
                    <div class="flex-1">
                        @include('public.partials.field', ['field' => $field])
                    </div>
                </div>
            @endforeach
        </section>
    @endforeach

    <p class="text-xs text-tk-muted mt-2" data-draft-status hidden data-saved-label="{{ __('tk.draft_saved') }}"></p>

    <div class="flex flex-wrap justify-between gap-3 mt-6">
        <button type="button" class="tk-btn-secondary min-w-[7rem]" data-wizard-prev hidden>Previous</button>
        @if (! empty($draftSaveUrl))
            <button type="button" class="tk-btn-secondary text-sm" data-wizard-save-draft>{{ __('tk.save_draft') }}</button>
        @endif
        <div class="flex-1"></div>
        <button type="button" class="tk-btn-primary min-w-[7rem]" data-wizard-next>Next</button>
        <button type="submit" class="tk-btn-primary min-w-[7rem]" data-wizard-submit hidden>Submit</button>
    </div>
    <p class="text-xs text-tk-muted mt-3">Tip: Alt + ← / → to move between sections with the keyboard.</p>
</form>
