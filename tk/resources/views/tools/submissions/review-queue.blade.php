<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Review queue — {{ $tool->name }}</h2>
                <p class="text-sm text-tk-muted">Submissions awaiting review</p>
            </div>
            <a href="{{ route('tools.submissions.index', $tool) }}" class="tk-btn-secondary">All submissions</a>
        </div>
    </x-slot>

    <div class="tk-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-tk-soft text-left">
                <tr>
                    <th class="p-3">Received</th>
                    <th class="p-3">Form</th>
                    <th class="p-3">Collector</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                    <tr class="border-t border-tk-line">
                        <td class="p-3">{{ $submission->received_at?->format('Y-m-d H:i') }}</td>
                        <td class="p-3">{{ $submission->form?->title }}</td>
                        <td class="p-3">{{ $submission->collector?->name ?? 'Public' }}</td>
                        <td class="p-3 font-mono">{{ $submission->status }}</td>
                        <td class="p-3 text-right">
                            <a href="{{ route('tools.submissions.show', [$tool, $submission]) }}" class="text-tk-accent font-medium">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-tk-muted">No submissions in the queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $submissions->links() }}</div>
</x-app-layout>
