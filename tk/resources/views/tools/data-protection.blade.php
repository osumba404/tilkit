<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="'Data protection - '.$tool->name" subtitle="Kenya Data Protection Act, 2019">
            <x-slot:actions>
                <a href="{{ route('public.tools.privacy', $tool) }}" class="tk-btn-secondary text-sm" target="_blank" rel="noopener">Public privacy notice</a>
                @if ($tool->module === 'tilkit_weather')
                    <a href="{{ route('tools.tilkit.team', $tool) }}" class="tk-btn-secondary text-sm">Team</a>
                @else
                    <a href="{{ route('tools.show', $tool) }}" class="tk-btn-secondary text-sm">Back to tool</a>
                @endif
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if ($tilkitNav ?? null)
        @include('tools.tilkit.partials.subnav', ['tilkitNav' => $tilkitNav])
    @endif

    @if (session('status'))
        <div class="tk-callout mb-6 text-sm">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route($governanceUpdateRoute, $tool) }}" class="tk-card p-6 space-y-5 max-w-3xl mb-10">
        @csrf
        @method('PATCH')
        <h2 class="font-semibold">Controller &amp; lawful basis (s.25–30)</h2>
        <div>
            <label class="text-sm font-medium">Processing purpose</label>
            <textarea name="purpose" rows="3" required class="tk-input w-full mt-1">{{ old('purpose', $tool->purpose) }}</textarea>
        </div>
        <div>
            <label class="text-sm font-medium">Lawful basis</label>
            <select name="lawful_basis" class="tk-input w-full mt-1">
                @foreach ($lawfulBases as $code => $label)
                    <option value="{{ $code }}" @selected(old('lawful_basis', $tool->lawful_basis) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">Data controller name</label>
                <input type="text" name="data_controller_name" class="tk-input w-full mt-1" value="{{ old('data_controller_name', $tool->data_controller_name) }}">
            </div>
            <div>
                <label class="text-sm font-medium">Controller email</label>
                <input type="email" name="data_controller_email" class="tk-input w-full mt-1" value="{{ old('data_controller_email', $tool->data_controller_email) }}">
            </div>
            <div>
                <label class="text-sm font-medium">Data protection contact</label>
                <input type="email" name="data_protection_contact_email" class="tk-input w-full mt-1" value="{{ old('data_protection_contact_email', $tool->data_protection_contact_email) }}">
            </div>
            <div>
                <label class="text-sm font-medium">ODPC registration no.</label>
                <input type="text" name="odpc_registration_number" class="tk-input w-full mt-1" value="{{ old('odpc_registration_number', $tool->odpc_registration_number) }}">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium">External privacy policy URL</label>
            <input type="url" name="privacy_policy_url" class="tk-input w-full mt-1" value="{{ old('privacy_policy_url', $tool->privacy_policy_url) }}">
        </div>
        <div>
            <label class="text-sm font-medium">Retention (months)</label>
            <input type="number" name="retention_months" min="1" max="600" class="tk-input w-full mt-1 max-w-xs" value="{{ old('retention_months', $tool->retention_months ?? config('kenya_dpa.default_retention_months')) }}">
            <p class="text-xs text-tk-muted mt-1">Enforced by <code class="text-xs">php artisan tilkit:enforce-retention</code> (schedule monthly).</p>
        </div>
        <div>
            <label class="text-sm font-medium">Retention policy summary</label>
            <textarea name="retention_policy_summary" rows="2" class="tk-input w-full mt-1">{{ old('retention_policy_summary', $tool->retention_policy_summary) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="cross_border_transfers_allowed" value="1" class="tk-checkbox" @checked(old('cross_border_transfers_allowed', $tool->cross_border_transfers_allowed))>
            Cross-border transfers allowed (s.25(h)) with safeguards documented below
        </label>
        <div>
            <label class="text-sm font-medium">Cross-border safeguards</label>
            <textarea name="cross_border_safeguards" rows="2" class="tk-input w-full mt-1">{{ old('cross_border_safeguards', $tool->cross_border_safeguards) }}</textarea>
        </div>
        <div>
            <label class="text-sm font-medium">Custom privacy notice (optional)</label>
            <textarea name="personal_data_notice" rows="8" class="tk-input w-full mt-1 font-mono text-xs">{{ old('personal_data_notice', $tool->personal_data_notice) }}</textarea>
        </div>
        <button type="submit" class="tk-btn-primary">Save governance</button>
    </form>

    @if ($inventory !== [])
        <div class="tk-card p-6 mb-10 max-w-3xl">
            <h2 class="font-semibold mb-3">Workbook personal data inventory</h2>
            <ul class="text-sm space-y-1 text-tk-muted">
                @foreach ($inventory as $letter => $meta)
                    <li><span class="font-mono text-tk-ink">{{ $letter }}</span> - {{ $meta['description'] ?? '' }} ({{ $meta['category'] ?? '' }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tk-card p-6 max-w-4xl">
        <h2 class="font-semibold mb-4">Data subject requests (s.26–27)</h2>
        @forelse ($requests as $req)
            <form method="POST" action="{{ route($requestResolveRoute, [$tool, $req]) }}" class="border border-tk-line rounded-lg p-4 mb-4 text-sm">
                @csrf
                @method('PATCH')
                <p class="font-medium">{{ $req->requester_name }} &lt;{{ $req->requester_email }}&gt;</p>
                <p class="text-tk-muted">{{ $req->request_type->label() }} · {{ $req->created_at->format('Y-m-d H:i') }} · {{ $req->status }}</p>
                @if ($req->record_reference)
                    <p class="text-tk-muted">Row: {{ $req->record_reference }}</p>
                @endif
                @if ($req->details)
                    <p class="mt-2">{{ $req->details }}</p>
                @endif
                <div class="flex flex-wrap gap-3 mt-3 items-end">
                    <select name="status" class="tk-input text-sm py-2">
                        @foreach (['pending', 'in_progress', 'completed', 'rejected'] as $st)
                            <option value="{{ $st }}" @selected($req->status === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="anonymize_record_uid" class="tk-input text-sm py-2" placeholder="Anonymize indicator UID on erasure">
                    <input type="text" name="resolution_notes" class="tk-input text-sm py-2 flex-1 min-w-[12rem]" placeholder="Resolution notes" value="{{ $req->resolution_notes }}">
                    <button type="submit" class="tk-btn-secondary text-sm">Update</button>
                </div>
            </form>
        @empty
            <p class="text-tk-muted text-sm">No requests yet. Public form: <a href="{{ route('public.tools.data-subject-request', $tool) }}" class="text-tk-accent underline">{{ route('public.tools.data-subject-request', $tool) }}</a></p>
        @endforelse
    </div>
</x-app-layout>
