<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$tool->name" :subtitle="$tool->code.' · Your role: '.$capabilities->label()">
            <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('platform.datasets.index') }}" class="tk-btn-secondary">My datasets</a>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT weather</a>
                @if ($capabilities->canViewAnalytics())
                    <a href="{{ route('tools.analytics', $tool) }}" class="tk-btn-secondary">Analytics</a>
                @endif
                @if ($capabilities->canReviewSubmissions())
                    <a href="{{ route('tools.submissions.review', $tool) }}" class="tk-btn-secondary">Review queue</a>
                @endif
                <a href="{{ route('tools.submissions.index', $tool) }}" class="tk-btn-secondary">
                    {{ $capabilities->canViewAllSubmissions() ? 'Submissions' : 'My submissions' }}
                </a>
                @if ($capabilities->canExportData())
                    <a href="{{ route('tools.export', $tool) }}" class="tk-btn-secondary">Export CSV</a>
                @endif
                @if ($capabilities->canManageTool())
                    <a href="{{ route('tools.settings.edit', $tool) }}" class="tk-btn-secondary">Settings</a>
                    <a href="{{ route('tools.forms.create', $tool) }}" class="tk-btn-primary">New form</a>
                @endif
            </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @include('tools.partials.public-collection', ['tool' => $tool])

    @if ($capabilities->canManageTool())
        <div class="tk-card p-6 mb-8">
            <h3 class="font-semibold mb-2">Invite team members</h3>
            <p class="text-sm text-tk-muted mb-4">Role templates: viewer (read-only), collector (submit own records), admin (manage tool).</p>
            <form method="POST" action="{{ route('tools.invitations.store', $tool) }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div class="flex-1 min-w-[14rem]">
                    <label class="text-xs text-tk-muted">Email address</label>
                    <input type="email" name="email" required class="w-full rounded-md border border-tk-line px-3 py-2 text-sm" placeholder="person@example.com">
                    @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs text-tk-muted">Role</label>
                    <select name="role" class="rounded-md border border-tk-line px-3 py-2 text-sm">
                        <option value="collector">Field collector</option>
                        <option value="viewer">Viewer</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="can_export" value="1" class="rounded border-tk-line text-tk-accent"> Can export</label>
                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="can_publish_forms" value="1" class="rounded border-tk-line text-tk-accent"> Can publish forms</label>
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
                                    <li>{{ $invite->email }} · {{ $invite->role->label() }} · expires {{ $invite->expires_at->diffForHumans() }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if ($auditLogs->isNotEmpty())
            <div class="tk-card p-6 mb-8">
                <h3 class="font-semibold mb-3">Audit trail</h3>
                <ul class="text-sm space-y-2 text-tk-muted">
                    @foreach ($auditLogs as $log)
                        <li>
                            <span class="font-mono text-tk-ink">{{ $log->created_at?->format('Y-m-d H:i') }}</span>
                            · {{ $log->user?->name ?? 'System' }}
                            · {{ str_replace('.', ' ', $log->action) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
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
                    @if ($form->status === 'published' && $capabilities->canFillForms())
                        <a class="text-sm text-tk-accent font-medium" href="{{ route('public.forms.show', $form) }}">Fill form</a>
                    @endif
                    @if ($form->status === 'published')
                        <p class="text-xs text-tk-muted font-mono mt-1">{{ route('public.forms.show', $form) }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($capabilities->canManageTool())
                        <a href="{{ route('tools.forms.builder', [$tool, $form]) }}" class="tk-btn-secondary text-sm">Form builder</a>
                        <form method="POST" action="{{ route('tools.forms.duplicate', [$tool, $form]) }}">
                            @csrf
                            <button type="submit" class="tk-btn-secondary text-sm">Duplicate</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-tk-muted text-sm">No forms yet.</p>
        @endforelse
    </div>
</x-app-layout>
