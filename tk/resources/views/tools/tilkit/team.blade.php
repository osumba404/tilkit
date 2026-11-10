<x-app-layout>
    @include('tools.tilkit.partials.subnav')

    <div class="flex justify-end gap-2 mb-4">
        <a href="{{ $tool->dataProtectionAdminUrl() }}" class="tk-btn-secondary text-sm">Data protection (Kenya DPA)</a>
        <a href="{{ route('tools.settings.edit', $tool) }}" class="tk-btn-secondary text-sm">Consent &amp; branding</a>
    </div>

    @include('tools.partials.governance-kenya-dpa', ['tool' => $tool])

    <div class="tk-card p-6 mb-8">
        <h3 class="font-semibold text-tk-ink mb-2">Invite team members</h3>
        <p class="text-sm text-tk-muted mb-4">
            Tool roles control capture, exports, and setup. <strong>Field collector</strong> and <strong>member</strong> share the same permissions
            (member is used for auto-enrolled accounts on single-tool deployments). TILKIT personas guard data desk, confirmations, and integration publish.
        </p>
        <form method="POST" action="{{ route('tools.invitations.store', $tool) }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div class="flex-1 min-w-[14rem]">
                <label class="text-xs text-tk-muted block mb-1">Email address</label>
                <input type="email" name="email" required class="tk-input text-sm py-2" placeholder="person@example.com">
            </div>
            <div>
                <label class="text-xs text-tk-muted block mb-1">Tool role</label>
                <select name="role" class="tk-input text-sm py-2">
                    <option value="collector">Field collector</option>
                    <option value="member">Field collector (member)</option>
                    <option value="viewer">Viewer (read-only)</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="min-w-[12rem]">
                <label class="text-xs text-tk-muted block mb-1">TILKIT persona</label>
                <select name="tilkit_persona" class="tk-input text-sm py-2">
                    <option value="">Default for tool role</option>
                    @foreach ($tilkitPersonas as $persona)
                        <option value="{{ $persona->value }}">{{ $persona->label() }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-xs text-tk-muted pb-2">
                <input type="checkbox" name="can_export" value="1" class="tk-checkbox">
                Can export
            </label>
            <button type="submit" class="tk-btn-primary text-sm">Send invitation</button>
        </form>
        @if ($tool->invitations->isNotEmpty())
            <div class="mt-6">
                <p class="font-medium text-tk-ink mb-2 text-sm">Pending invitations</p>
                <ul class="space-y-1 text-sm text-tk-muted">
                    @foreach ($tool->invitations as $invite)
                        <li>
                            {{ $invite->email }}
                            · {{ $invite->role->label() }}
                            @if ($invite->tilkit_persona)
                                · {{ \App\Enums\Tilkit\TilkitPersona::tryFrom($invite->tilkit_persona)?->label() ?? $invite->tilkit_persona }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="tk-card p-6">
        <h3 class="font-semibold text-tk-ink mb-4">Team members</h3>
        <div class="space-y-6">
            @foreach ($tool->members as $member)
                @php
                    $resolvedPersona = $member->tilkit_persona
                        ? (\App\Enums\Tilkit\TilkitPersona::tryFrom($member->tilkit_persona)?->label() ?? $member->tilkit_persona)
                        : 'Default for tool role';
                @endphp
                <form method="POST" action="{{ route('tools.tilkit.team.members.update', [$tool, $member]) }}" class="border border-tk-line/60 rounded-xl p-4 flex flex-wrap gap-3 items-end">
                    @csrf
                    @method('PATCH')
                    <div class="min-w-[12rem] flex-1">
                        <p class="font-medium text-tk-ink text-sm">{{ $member->user?->name }}</p>
                        <p class="text-xs text-tk-muted">{{ $member->user?->email }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-tk-muted block mb-1">Tool role</label>
                        <select name="role" class="tk-input text-sm py-2">
                            @foreach ([\App\Enums\ToolMemberRole::Admin, \App\Enums\ToolMemberRole::Collector, \App\Enums\ToolMemberRole::Member, \App\Enums\ToolMemberRole::Viewer] as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected($member->role === $roleOption)>
                                    {{ $roleOption->label() }}{{ $roleOption === \App\Enums\ToolMemberRole::Member ? ' (member)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[12rem]">
                        <label class="text-xs text-tk-muted block mb-1">TILKIT persona <span class="text-tk-muted">({{ $resolvedPersona }})</span></label>
                        <select name="tilkit_persona" class="tk-input text-sm py-2">
                            <option value="">Default for tool role</option>
                            @foreach ($tilkitPersonas as $persona)
                                <option value="{{ $persona->value }}" @selected($member->tilkit_persona === $persona->value)>{{ $persona->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-tk-muted pb-2">
                        <input type="checkbox" name="can_export" value="1" class="tk-checkbox" @checked($member->can_export === true)>
                        Can export
                    </label>
                    <button type="submit" class="tk-btn-secondary text-sm">Save</button>
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>
