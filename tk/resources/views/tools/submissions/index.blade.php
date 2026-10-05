<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold">Submissions — {{ $tool->name }}</h2>
    </x-slot>

    <form method="GET" class="tk-card p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-xs text-tk-muted">Status</label>
            <select name="status" class="rounded border border-tk-line text-sm">
                <option value="">All</option>
                @foreach (['draft','submitted','in_review','approved','rejected'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-tk-muted">Form</label>
            <select name="form_id" class="rounded border border-tk-line text-sm">
                <option value="">All</option>
                @foreach ($forms as $f)
                    <option value="{{ $f->id }}" @selected(request('form_id') == $f->id)>{{ $f->title }}</option>
                @endforeach
            </select>
        </div>
        <button class="tk-btn-secondary text-sm">Filter</button>
    </form>

    <div class="tk-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-tk-soft text-left">
                <tr>
                    <th class="p-3">Received</th>
                    <th class="p-3">Form</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Channel</th>
                    <th class="p-3">Collector</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($submissions as $sub)
                    <tr class="border-t border-tk-line hover:bg-tk-soft/50">
                        <td class="p-3"><a href="{{ route('tools.submissions.show', [$tool, $sub]) }}" class="text-tk-accent">{{ $sub->received_at?->format('Y-m-d H:i') }}</a></td>
                        <td class="p-3">{{ $sub->form?->title }}</td>
                        <td class="p-3">{{ $sub->status }}</td>
                        <td class="p-3">{{ $sub->channel }}</td>
                        <td class="p-3">{{ $sub->collector?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $submissions->links() }}</div>
</x-app-layout>
