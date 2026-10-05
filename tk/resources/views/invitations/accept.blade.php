<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <p class="font-semibold text-gray-900">Join {{ $invitation->project->name }} on Tilkit</p>
        <p class="mt-2">You were invited as a <strong>field collector</strong>. You can submit data and view only your own records.</p>
        <p class="mt-2 text-xs">Invitation sent to <span class="font-mono">{{ $invitation->email }}</span></p>
    </div>

    <div class="flex flex-col gap-3">
        <a href="{{ route('login') }}" class="tk-btn-primary text-center">Log in to accept</a>
        <a href="{{ route('register') }}" class="tk-btn-secondary text-center">Create an account</a>
    </div>
</x-guest-layout>
