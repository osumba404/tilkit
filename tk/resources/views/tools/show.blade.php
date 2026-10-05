<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">{{ $tool->name }}</h2>
                <p class="text-sm text-tk-muted font-mono">{{ $tool->code }} · Your role: {{ $role?->value ?? '—' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tools.analytics', $tool) }}" class="tk-btn-secondary">Analytics</a>
                <a href="{{ route('tools.submissions.index', $tool) }}" class="tk-btn-secondary">Submissions</a>
                <a href="{{ route('tools.export', $tool) }}" class="tk-btn-secondary">Export CSV</a>
                @if ($role?->canManageTool())
                    <a href="{{ route('tools.forms.create', $tool) }}" class="tk-btn-primary">New form</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="tk-card p-6 mb-8">
        <h3 class="font-semibold mb-2">Governance</h3>
        <p class="text-sm text-tk-muted">{{ $tool->purpose }}</p>
        <p class="text-xs text-tk-muted mt-2">Lawful basis: {{ str_replace('_', ' ', $tool->lawful_basis) }}</p>
    </div>

    <h3 class="font-semibold mb-4">Forms</h3>
    <div class="space-y-3">
        @forelse ($tool->forms as $form)
            <div class="tk-card p-4 flex flex-wrap justify-between gap-4">
                <div>
                    <p class="font-medium">{{ $form->title }}</p>
                    <p class="text-sm text-tk-muted">{{ $form->code }} · {{ $form->status }}</p>
                    @if ($form->status === 'published')
                        <a class="text-sm text-tk-accent" href="{{ route('public.forms.show', $form) }}" target="_blank">Public link ↗</a>
                    @endif
                </div>
                @if ($role?->canManageTool())
                    <a href="{{ route('tools.forms.builder', [$tool, $form]) }}" class="tk-btn-secondary text-sm">Form builder</a>
                @endif
            </div>
        @empty
            <p class="text-tk-muted text-sm">No forms yet.</p>
        @endforelse
    </div>
</x-app-layout>
