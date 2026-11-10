@php
    $lawfulLabel = $tool->lawfulBasisLabel();
    $retention = $tool->retention_months ?? config('kenya_dpa.default_retention_months');
@endphp
<div class="tk-card p-5 mb-6 text-sm">
    <h3 class="font-semibold text-tk-ink mb-2">Data protection (Kenya DPA 2019)</h3>
    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-tk-muted">
        <div>
            <dt class="text-xs uppercase tracking-wide">Lawful basis</dt>
            <dd class="text-tk-ink">{{ $lawfulLabel }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide">Retention</dt>
            <dd class="text-tk-ink">{{ $retention }} months @if ($tool->retention_policy_summary)<span class="block text-xs mt-0.5">{{ $tool->retention_policy_summary }}</span>@endif</dd>
        </div>
        @if ($tool->data_controller_name || $tool->data_controller_email)
            <div>
                <dt class="text-xs uppercase tracking-wide">Data controller</dt>
                <dd class="text-tk-ink">{{ $tool->data_controller_name ?: '—' }}@if ($tool->data_controller_email)<span class="block text-xs">{{ $tool->data_controller_email }}</span>@endif</dd>
            </div>
        @endif
        @if ($tool->data_protection_contact_email)
            <div>
                <dt class="text-xs uppercase tracking-wide">Privacy contact</dt>
                <dd class="text-tk-ink">{{ $tool->data_protection_contact_email }}</dd>
            </div>
        @endif
        @if ($tool->odpc_registration_number)
            <div>
                <dt class="text-xs uppercase tracking-wide">ODPC registration</dt>
                <dd class="text-tk-ink font-mono text-xs">{{ $tool->odpc_registration_number }}</dd>
            </div>
        @endif
        <div>
            <dt class="text-xs uppercase tracking-wide">Cross-border transfer</dt>
            <dd class="text-tk-ink">{{ $tool->cross_border_transfers_allowed ? 'Allowed (safeguards documented)' : 'Not permitted by default' }}</dd>
        </div>
    </dl>
    <div class="flex flex-wrap gap-3 mt-4">
        <a href="{{ route('public.tools.privacy', $tool) }}" class="text-tk-accent text-xs underline" target="_blank" rel="noopener">Public privacy notice</a>
        <a href="{{ route('public.tools.data-subject-request', $tool) }}" class="text-tk-accent text-xs underline" target="_blank" rel="noopener">Data subject request form</a>
        @if (($capabilities ?? null)?->canManageTool())
            <a href="{{ $tool->dataProtectionAdminUrl() }}" class="text-tk-accent text-xs underline">Manage governance</a>
        @endif
    </div>
</div>
