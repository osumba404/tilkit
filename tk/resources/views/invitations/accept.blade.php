<x-guest-layout
    title="Join {{ $invitation->project->name }} | Tilkit"
    meta-description="Accept your invitation to contribute field data on Tilkit."
    :canonical="url()->current()"
    robots="noindex, nofollow"
>
    <x-auth-heading title="Join {{ $invitation->project->name }}">
        You were invited as a <strong class="text-tk-ink">field collector</strong>. You can submit data and view only your own records. Invitation sent to <span class="font-mono text-xs">{{ $invitation->email }}</span>.
    </x-auth-heading>

    <nav class="flex flex-col gap-3" aria-label="Sign in options">
        <a href="{{ route('login') }}" class="tk-btn-primary text-center">Log in to accept</a>
        <a href="{{ route('register') }}" class="tk-btn-secondary text-center">Create an account</a>
    </nav>
</x-guest-layout>
