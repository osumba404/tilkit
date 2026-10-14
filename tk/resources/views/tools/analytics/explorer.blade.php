<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Submissions explorer" :subtitle="$tool->name" eyebrow="Tool analytics" />
        <p class="text-sm text-tk-muted">{{ $tool->name }}</p>
    </x-slot>

    <x-analytics.acc-shell context="tool" :acc-section="$accSection" :tool="$tool" title="Submissions explorer" subtitle="Sortable list with global filters applied">
        @include('analytics.partials.filter-bar', ['filters' => $filters, 'forms' => $forms, 'showFormFilter' => true])

        <div class="tk-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-tk-soft text-left">
                    <tr>
                        <th class="p-3">Received</th>
                        <th class="p-3">Form</th>
                        <th class="p-3">Collector</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Channel</th>
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
                            <td class="p-3">{{ $submission->channel }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('tools.submissions.show', [$tool, $submission]) }}" class="text-tk-accent font-medium">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-tk-muted">No submissions match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $submissions->links() }}</div>
    </x-analytics.acc-shell>
</x-app-layout>
