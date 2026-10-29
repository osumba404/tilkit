<x-app-layout>
    @include('tools.tilkit.partials.subnav')

    <div class="flex justify-end mb-4">
        <a href="{{ route('tools.settings.edit', $tool) }}" class="tk-btn-secondary text-sm">Consent &amp; branding</a>
    </div>

    @if (session('status'))
        <div class="tk-card p-4 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    <div class="tk-card p-6 mb-8">
        <h3 class="font-semibold text-tk-ink mb-2">Governance</h3>
        <p class="text-sm text-tk-muted">{{ $tool->purpose }}</p>
        <p class="text-xs text-tk-muted mt-2">Lawful basis: {{ str_replace('_', ' ', $tool->lawful_basis) }}</p>
    </div>

    <div class="tk-card p-6 mb-8">
        <h3 class="font-semibold text-tk-ink mb-2">Invite team members</h3>
        <p class="text-sm text-tk-muted mb-4">Roles control capture, data desk access, and exports for this shared weather log.</p>
        <form method="POST" action="{{ route('tools.invitations.store', $tool) }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div class="flex-1 min-w-[14rem]">
                <label class="text-xs text-tk-muted block mb-1">Email address</label>
                <input type="email" name="email" required class="tk-input text-sm py-2" placeholder="person@example.com">
            </div>
            <div>
                <label class="text-xs text-tk-muted block mb-1">Role</label>
                <select name="role" class="tk-input text-sm py-2">
                    <option value="collector">Field collector</option>
                    <option value="viewer">Viewer</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-xs text-tk-muted pb-2">
                <input type="checkbox" name="can_export" value="1" class="tk-checkbox">
                Can export
            </label>
            <button type="submit" class="tk-btn-primary text-sm">Send invitation</button>
        </form>
        <div class="mt-6 grid md:grid-cols-2 gap-6 text-sm">
            <div>
                <p class="font-medium text-tk-ink mb-2">Team</p>
                <ul class="space-y-1 text-tk-muted">
                    @foreach ($tool->members as $member)
                        <li>{{ $member->user?->name }} · {{ $member->user?->email }} · {{ $member->role->label() }}</li>
                    @endforeach
                </ul>
            </div>
            @if ($tool->invitations->isNotEmpty())
                <div>
                    <p class="font-medium text-tk-ink mb-2">Pending invitations</p>
                    <ul class="space-y-1 text-tk-muted">
                        @foreach ($tool->invitations as $invite)
                            <li>{{ $invite->email }} · {{ $invite->role->label() }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
