@php
    $publicUrl = $tool->publicCollectUrl();
@endphp
@if ($tool->public_collection_enabled)
    <div class="tk-card p-6 mb-8">
        <h3 class="font-semibold mb-2">Public collection link</h3>
        <p class="text-sm text-tk-muted mb-4">Share this page or QR code so anyone can fill published forms. When you are signed in (including as administrator), submissions are linked to your account.</p>
        <div class="flex flex-col md:flex-row gap-6 items-start">
            <a href="{{ route('public.tools.collect', $tool) }}" target="_blank" rel="noopener" class="shrink-0 block rounded-lg border border-tk-line bg-white p-2 hover:border-tk-accent">
                <img src="{{ route('public.tools.qr', $tool) }}" width="160" height="160" alt="QR code">
            </a>
            <div class="flex-1 w-full space-y-2">
                <input type="text" readonly value="{{ $publicUrl }}" id="tool-public-url-{{ $tool->id }}"
                    class="w-full rounded-md border border-tk-line px-3 py-2 text-sm font-mono bg-tk-soft">
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="tk-btn-secondary text-sm"
                        onclick="navigator.clipboard.writeText(document.getElementById('tool-public-url-{{ $tool->id }}').value)">
                        Copy link
                    </button>
                    <a href="{{ route('public.tools.collect', $tool) }}" target="_blank" rel="noopener" class="tk-btn-primary text-sm">
                        Open collection page
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
