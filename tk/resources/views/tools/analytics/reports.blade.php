<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">Reports</h2>
        <p class="text-sm text-tk-muted">{{ $tool->name }}</p>
    </x-slot>

    <x-analytics.acc-shell context="tool" :acc-section="$accSection" :tool="$tool" title="Reports & exports" subtitle="Download data and manage scheduled delivery">
        <div class="grid md:grid-cols-2 gap-6">
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-2">Manual export</h3>
                <p class="text-sm text-tk-muted mb-4">Full submission CSV for this tool.</p>
                @if ($capabilities->canExportData())
                    <a href="{{ route('tools.export', $tool) }}" class="tk-btn-primary text-sm">Download CSV</a>
                @else
                    <p class="text-sm text-amber-800">You do not have export permission.</p>
                @endif
            </div>
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-2">Scheduled export</h3>
                @if ($scheduledExport?->is_active)
                    <p class="text-sm">Recipients: {{ $scheduledExport->recipient_emails }}</p>
                    <p class="text-xs text-tk-muted mt-1">Frequency: {{ $scheduledExport->frequency }} · Last run: {{ $scheduledExport->last_run_at?->diffForHumans() ?? 'Never' }}</p>
                @else
                    <p class="text-sm text-tk-muted">Not configured.</p>
                @endif
                <a href="{{ route('tools.settings.edit', $tool) }}" class="tk-btn-secondary text-sm mt-4 inline-block">Configure in settings</a>
            </div>
        </div>
    </x-analytics.acc-shell>
</x-app-layout>
