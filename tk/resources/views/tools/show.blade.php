<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">{{ $tool->name }}</h2>
                <p class="text-sm text-tk-muted font-mono">{{ $tool->code }} · Your role: {{ $role?->label() ?? '—' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($role?->canViewAnalytics())
                    <a href="{{ route('tools.analytics', $tool) }}" class="tk-btn-secondary">Analytics</a>
                @endif
                <a href="{{ route('tools.submissions.index', $tool) }}" class="tk-btn-secondary">
                    {{ $role?->canViewAllSubmissions() ? 'Submissions' : 'My submissions' }}
                </a>
                @if ($role?->canExportData())
                    <a href="{{ route('tools.export', $tool) }}" class="tk-btn-secondary">Export CSV</a>
                @endif
                @if ($role?->canManageTool())
                    <a href="{{ route('tools.forms.create', $tool) }}" class="tk-btn-primary">New form</a>
                @endif
            </div>
        </div>
    </x-slot>

    @if ($role?->canManageTool())
        <div class="tk-card p-6 mb-8">
            <h3 class="font-semibold mb-2">Invite field collectors</h3>
            <p class="text-sm text-tk-muted mb-4">Send an email invitation. Collectors can fill forms and see only their own submissions.</p>
            <form method="POST" action="{{ route('tools.invitations.store', $tool) }}" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div class="flex-1 min-w-[14rem]">
                    <label class="text-xs text-tk-muted">Email address</label>
                    <input type="email" name="email" required class="w-full rounded-md border border-tk-line px-3 py-2 text-sm" placeholder="collector@example.com">
                    @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="tk-btn-primary text-sm">Send invitation</button>
            </form>
            @if ($tool->members->isNotEmpty() || $tool->invitations->isNotEmpty())
                <div class="mt-6 grid md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="font-medium mb-2">Team</p>
                        <ul class="space-y-1 text-tk-muted">
                            @foreach ($tool->members as $member)
                                <li>{{ $member->user?->name }} · {{ $member->user?->email }} · {{ $member->role->label() }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @if ($tool->invitations->isNotEmpty())
                        <div>
                            <p class="font-medium mb-2">Pending invitations</p>
                            <ul class="space-y-1 text-tk-muted">
                                @foreach ($tool->invitations as $invite)
                                    <li>{{ $invite->email }} · expires {{ $invite->expires_at->diffForHumans() }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="tk-card p-6 mb-8">
        <h3 class="font-semibold mb-2">Governance</h3>
        <p class="text-sm text-tk-muted">{{ $tool->purpose }}</p>
        <p class="text-xs text-tk-muted mt-2">Lawful basis: {{ str_replace('_', ' ', $tool->lawful_basis) }}</p>
    </div>

    <h3 class="font-semibold mb-4">Forms</h3>
    <div class="space-y-3">
        @forelse ($tool->forms as $form)
            <div class="tk-card p-4 flex flex-wrap justify-between gap-4">
                <div>
                    <p class="font-medium">{{ $form->title }}</p>
                    <p class="text-sm text-tk-muted">{{ $form->code }} · {{ $form->status }}</p>
                    @if ($form->status === 'published' && $role?->canFillForms())
                        <a class="text-sm text-tk-accent font-medium" href="{{ route('public.forms.show', $form) }}">Fill form (stay signed in to link your entries)</a>
                    @endif
                </div>
                @if ($role?->canManageTool())
                    <a href="{{ route('tools.forms.builder', [$tool, $form]) }}" class="tk-btn-secondary text-sm">Form builder</a>
                @endif
            </div>
        @empty
            <p class="text-tk-muted text-sm">No forms yet.</p>
        @endforelse
    </div>
</x-app-layout>
