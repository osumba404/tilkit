<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('tk.consent_title', ['tool' => $tool->name]) }}</title>
    @vite(['resources/css/app.css', 'resources/js/public-signature.js'])
</head>
<body class="font-body bg-tk-bg text-tk-ink min-h-screen">
@include('public.partials.branding', ['tool' => $tool])
<div class="max-w-xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-semibold">{{ __('tk.consent_heading') }}</h1>
    <div class="tk-card p-6 mt-6 prose prose-sm max-w-none text-tk-ink">
        {!! nl2br(e($tool->consent_text)) !!}
    </div>

    <form method="POST" action="{{ route('public.tools.consent.accept', $tool) }}" class="tk-card p-6 mt-6 space-y-4">
        @csrf
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="consent_ack" value="1" required class="mt-1 rounded border-tk-line text-tk-accent focus:ring-tk-accent">
            <span>{{ $tool->consent_ack_label ?: __('tk.consent_ack_default') }}</span>
        </label>

        @if ($tool->consent_require_signature)
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('tk.consent_signature') }}</label>
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
</div>
</body>
</html>
