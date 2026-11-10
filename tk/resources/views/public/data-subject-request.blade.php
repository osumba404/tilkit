<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data subject request - {{ $tool->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-tk-soft text-tk-ink antialiased">
    <main class="max-w-xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-semibold mb-2">Data subject request</h1>
        <p class="text-sm text-tk-muted mb-6">Kenya Data Protection Act, 2019 - Section 26</p>

        <form method="POST" action="{{ route('public.tools.data-subject-request.store', $tool) }}" class="tk-card p-6 space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium">Request type</label>
                <select name="request_type" required class="tk-input w-full mt-1">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Your name</label>
                <input type="text" name="requester_name" required class="tk-input w-full mt-1" value="{{ old('requester_name') }}">
            </div>
            <div>
                <label class="text-sm font-medium">Email</label>
                <input type="email" name="requester_email" required class="tk-input w-full mt-1" value="{{ old('requester_email') }}">
            </div>
            <div>
                <label class="text-sm font-medium">Phone (optional)</label>
                <input type="text" name="requester_phone" class="tk-input w-full mt-1" value="{{ old('requester_phone') }}">
            </div>
            <div>
                <label class="text-sm font-medium">Indicator row ID (optional)</label>
                <input type="text" name="record_reference" class="tk-input w-full mt-1" placeholder="e.g. KEN-01-ANI-CRO-001" value="{{ old('record_reference') }}">
            </div>
            <div>
                <label class="text-sm font-medium">Details</label>
                <textarea name="details" rows="4" class="tk-input w-full mt-1">{{ old('details') }}</textarea>
            </div>
            <button type="submit" class="tk-btn-primary w-full">Submit request</button>
            <p class="text-xs text-tk-muted">The data controller will respond within 30 days where practicable.</p>
        </form>
        <p class="mt-4 text-sm"><a href="{{ route('public.tools.privacy', $tool) }}" class="text-tk-accent underline">Back to privacy notice</a></p>
    </main>
</body>
</html>
