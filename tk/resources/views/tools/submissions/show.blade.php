<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold">Submission detail</h2>
        <p class="text-sm text-tk-muted font-mono">{{ $submission->client_uuid }}</p>
    </x-slot>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-3">Metadata</h3>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-tk-muted">Form</dt><dd>{{ $submission->form?->title }}</dd></div>
                <div class="flex justify-between"><dt class="text-tk-muted">Status</dt><dd>{{ $submission->status }}</dd></div>
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
                            {{ $answer->value_text ?? $answer->value_number ?? ($answer->value_json ? json_encode($answer->value_json) : '—') }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
    <details class="tk-card p-5 mt-6">
        <summary class="cursor-pointer font-semibold">Raw JSON (immutable record)</summary>
        <pre class="text-xs mt-3 overflow-x-auto bg-tk-soft p-3 rounded">{{ json_encode($submission->data, JSON_PRETTY_PRINT) }}</pre>
    </details>
</x-app-layout>
