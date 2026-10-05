<x-mail::message>
# Collect field data on Tilkit

**{{ $inviterName }}** invited you to join **{{ $toolName }}** as a field collector.

You will be able to fill in forms for this tool and review **only your own** submissions.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

This link expires in 14 days. If you do not have a Tilkit account yet, you can register using this email address after opening the link.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
