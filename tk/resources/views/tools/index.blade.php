<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="text-2xl font-bold">Tools</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('platform.datasets.index') }}" class="tk-btn-secondary">My datasets</a>
                <a href="{{ route('tools.create') }}" class="tk-btn-primary">Create tool</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-3">
        @foreach ($tools as $tool)
            <a href="{{ route('tools.show', $tool) }}" class="tk-card flex flex-wrap items-center justify-between gap-4 p-5 hover:border-tk-accent">
                <div>
                    <h3 class="font-semibold text-lg">{{ $tool->name }}</h3>
                    <p class="text-sm text-tk-muted">{{ $tool->purpose }}</p>
                </div>
                <div class="text-sm text-tk-muted text-right">
                    <p>{{ $tool->forms_count }} forms · {{ $tool->submissions_count }} submissions</p>
                    <p class="font-mono text-xs">{{ $tool->code }}</p>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $tools->links() }}</div>
</x-app-layout>
