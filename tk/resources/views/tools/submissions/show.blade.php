<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Submission detail" />
        <p class="text-sm text-tk-muted font-mono">{{ $submission->client_uuid }}</p>
    </x-slot>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-3">Metadata</h3>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-tk-muted">Form</dt><dd>{{ $submission->form?->title }}</dd></div>
                <div class="flex justify-between"><dt class="text-tk-muted">Status</dt><dd>{{ $submission->status }}</dd></div>
                @if ($submission->review_notes)
                    <div class="flex justify-between gap-4"><dt class="text-tk-muted shrink-0">Review notes</dt><dd class="text-right">{{ $submission->review_notes }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-tk-muted">Channel</dt><dd>{{ $submission->channel }}</dd></div>
                <div class="flex justify-between"><dt class="text-tk-muted">Received</dt><dd>{{ $submission->received_at }}</dd></div>
            </dl>
        </div>
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-3">Answers</h3>
            <dl class="text-sm space-y-3">
                @foreach ($submission->answers as $answer)
                    <div>
                        <dt class="font-medium">{{ $answer->field?->label ?? $answer->field?->name }}</dt>
                        <dd class="text-tk-muted font-mono text-xs break-all">
                            {{ $answer->value_text ?? $answer->value_number ?? ($answer->value_json ? json_encode($answer->value_json) : '-') }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
    @php $capabilities = auth()->user()->toolCapabilitiesFor($tool); @endphp
    @if ($capabilities->canReviewSubmissions())
        <form method="POST" action="{{ route('tools.submissions.review.update', [$tool, $submission]) }}" class="tk-card p-5 mt-6 space-y-3">
            @csrf
            @method('PATCH')
            <h3 class="font-semibold">Review workflow</h3>
            <div class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-tk-muted">Status</label>
                    <select name="status" class="rounded-md border border-tk-line px-3 py-2 text-sm">
                        @foreach (['submitted', 'in_review', 'approved', 'rejected'] as $status)
                            <option value="{{ $status }}" @selected($submission->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[12rem]">
                    <label class="text-xs text-tk-muted">Notes to collector</label>
                    <input type="text" name="review_notes" value="{{ old('review_notes', $submission->review_notes) }}" class="w-full rounded-md border border-tk-line px-3 py-2 text-sm">
                </div>
                <button type="submit" class="tk-btn-primary text-sm">Update status</button>
            </div>
        </form>
    @endif

    <details class="tk-card p-5 mt-6">
        <summary class="cursor-pointer font-semibold">Raw JSON (immutable record)</summary>
        <pre class="text-xs mt-3 overflow-x-auto bg-tk-soft p-3 rounded">{{ json_encode($submission->data, JSON_PRETTY_PRINT) }}</pre>
    </details>
</x-app-layout>
