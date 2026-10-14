<x-guest-layout>
    <x-auth-heading title="Join {{ $invitation->project->name }}">
        You were invited as a <strong class="text-tk-ink">field collector</strong>. You can submit data and view only your own records. Invitation sent to <span class="font-mono text-xs">{{ $invitation->email }}</span>.
    </x-auth-heading>

    <div class="flex flex-col gap-3">
        <a href="{{ route('login') }}" class="tk-btn-primary text-center">Log in to accept</a>
        <a href="{{ route('register') }}" class="tk-btn-secondary text-center">Create an account</a>
    </div>
</x-guest-layout>
