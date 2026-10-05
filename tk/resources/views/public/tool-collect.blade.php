<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tool->name }} — Collect data</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-body bg-tk-bg text-tk-ink min-h-screen">
<div class="max-w-lg mx-auto px-4 py-10">
    <p class="text-xs text-tk-muted uppercase tracking-wide">Tilkit · Public collection</p>
    <h1 class="text-3xl font-semibold mt-1">{{ $tool->name }}</h1>
    @if ($tool->purpose)
        <p class="text-tk-muted mt-2 text-sm">{{ \Illuminate\Support\Str::limit($tool->purpose, 280) }}</p>
    @endif

    <div class="tk-card p-5 mt-8 flex flex-col sm:flex-row gap-6 items-center">
        <div class="shrink-0 rounded-lg border border-tk-line bg-white p-2">
            <img src="{{ route('public.tools.qr', $tool) }}" width="200" height="200" alt="QR code for this collection link" class="block">
        </div>
        <div class="flex-1 w-full text-sm">
            <p class="font-medium mb-2">Scan or share this link</p>
            <input type="text" readonly value="{{ $publicUrl }}" id="public-collect-url"
                class="w-full rounded-md border border-tk-line px-3 py-2 text-xs font-mono bg-tk-soft mb-2">
            <button type="button" class="tk-btn-secondary text-xs w-full" onclick="copyCollectUrl()">Copy link</button>
            <p class="text-xs text-tk-muted mt-3">Anyone with this link can open and submit published forms. Sign in to attach entries to your account.</p>
        </div>
    </div>

    <h2 class="font-semibold mt-10 mb-4">Forms</h2>
    <div class="space-y-3">
        @forelse ($tool->forms as $form)
            <a href="{{ route('public.forms.show', $form) }}" class="tk-card block p-4 hover:border-tk-accent transition-colors">
                <p class="font-medium">{{ $form->title }}</p>
                @if ($form->description)
                    <p class="text-sm text-tk-muted mt-1">{{ \Illuminate\Support\Str::limit($form->description, 120) }}</p>
                @endif
                <p class="text-sm text-tk-accent font-medium mt-2">Open form →</p>
            </a>
        @empty
            <p class="text-sm text-tk-muted">No published forms yet.</p>
        @endforelse
    </div>
</div>
<script>
function copyCollectUrl() {
    const el = document.getElementById('public-collect-url');
    el.select();
    navigator.clipboard?.writeText(el.value);
}
</script>
</body>
</html>
