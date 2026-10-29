<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tools">
            <x-slot:actions>
                @unless ($tilkitWeatherTool ?? null)
                    <a href="{{ route('tools.create') }}" class="tk-btn-primary">Create tool</a>
                @endunless
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-3">
        @foreach ($tools as $tool)
            <a href="{{ route('tools.show', $tool) }}" class="tk-card-interactive flex flex-wrap items-center justify-between gap-4 p-5">
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
