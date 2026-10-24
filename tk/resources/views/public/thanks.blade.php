<x-layouts.public title="Thank you - Tilkit" :centered="true">
    <div class="tk-card p-8 text-center shadow-tk-md">
        <h1 class="font-display text-2xl font-bold text-tk-ink">Thank you</h1>
        <p class="text-tk-muted mt-3 text-sm">Your response to <strong class="text-tk-ink">{{ $form->title }}</strong> was recorded securely.</p>
        <div class="flex flex-col gap-2 mt-6">
            @if ($form->project?->public_collection_enabled)
                <a href="{{ route('public.tools.collect', $form->project) }}" class="tk-btn-primary">Back to full tool form</a>
            @endif
            <a href="{{ route('home') }}" class="tk-btn-secondary">Back to Tilkit</a>
        </div>
    </div>
</x-layouts.public>
