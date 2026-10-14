<x-app-layout>
    <x-slot name="header">
        <x-page-header title="My datasets" subtitle="Upload CSV or Excel for natural-language analysis" />
    </x-slot>

    <x-analytics.acc-shell context="datasets" acc-section="datasets" title="Dataset workspace" subtitle="Ingestion, mapping, and sandboxed analysis">
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 tk-card p-5">
                <h3 class="font-semibold mb-4">Your datasets</h3>
                <ul class="divide-y divide-tk-line text-sm">
                    @forelse ($datasets as $ds)
                        <li class="py-3 flex justify-between gap-4">
                            <div>
                                <a href="{{ route('platform.datasets.show', $ds) }}" class="font-medium text-tk-accent">{{ $ds->name }}</a>
                                <p class="text-xs text-tk-muted">{{ $ds->row_count }} rows · {{ $ds->column_count }} columns · {{ $ds->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="text-xs text-tk-muted">{{ $ds->uploader?->name }}</span>
                        </li>
                    @empty
                        <li class="py-6 text-tk-muted">No datasets yet. Upload a file to get started.</li>
                    @endforelse
                </ul>
                <div class="mt-4">{{ $datasets->links() }}</div>
            </div>

            <div class="tk-card p-5">
                <h3 class="font-semibold mb-2">Upload</h3>
                <p class="text-xs text-tk-muted mb-4">Headers row 1 · types inferred from sample rows · staged in a private workspace.</p>
                <form method="POST" action="{{ route('platform.datasets.store') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="text" name="name" placeholder="Dataset name (optional)" class="w-full rounded-md border border-tk-line px-3 py-2 text-sm">
                    <input type="file" name="dataset" accept=".csv,.xlsx,.xls,.txt" required class="w-full text-sm">
                    @error('dataset')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                    <button type="submit" class="tk-btn-primary text-sm w-full">Upload & profile</button>
                </form>
            </div>
        </div>
    </x-analytics.acc-shell>
</x-app-layout>
