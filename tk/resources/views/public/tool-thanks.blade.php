<x-layouts.public title="Thank you — Tilkit" :centered="true">
    <div class="tk-card p-8 text-center shadow-tk-md">
        <h1 class="font-display text-2xl font-bold text-tk-ink">Thank you</h1>
        <p class="text-tk-muted mt-3 text-sm">Your responses for <strong class="text-tk-ink">{{ $tool->name }}</strong> were recorded securely.</p>
        <a href="{{ route('public.tools.collect', $tool) }}" class="tk-btn-primary inline-flex mt-6">Submit another</a>
    </div>
</x-layouts.public>
