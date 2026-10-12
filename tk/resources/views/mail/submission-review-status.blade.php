<x-mail::message>
# Submission {{ $status === 'approved' ? 'approved' : 'review update' }}

Your submission for **{{ $submission->form?->title ?? 'a form' }}** in **{{ $submission->project->name }}** is now **{{ str_replace('_', ' ', $status) }}**.

@if ($submission->review_notes)
**Reviewer notes:** {{ $submission->review_notes }}
@endif

<x-mail::button :url="route('tools.submissions.show', [$submission->project, $submission])">
View submission
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
