<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="'Settings - '.$tool->name" subtitle="Consent, branding, and locale for public collection">
            <x-slot:actions>
                <a href="{{ route('tools.show', $tool) }}" class="tk-btn-secondary">Back to tool</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('tools.settings.update', $tool) }}" class="tk-card p-6 space-y-6 max-w-2xl">
        @csrf
        @method('PATCH')

        <div>
            <label class="text-sm font-medium">Consent text</label>
            <p class="text-xs text-tk-muted mb-1">Shown once before the public wizard when filled in.</p>
            <textarea name="consent_text" rows="6" class="w-full rounded-md border border-tk-line px-3 py-2 text-sm">{{ old('consent_text', $tool->consent_text) }}</textarea>
        </div>

        <div>
            <label class="text-sm font-medium">Acknowledgement checkbox label</label>
            <input type="text" name="consent_ack_label" value="{{ old('consent_ack_label', $tool->consent_ack_label) }}" class="w-full rounded-md border border-tk-line px-3 py-2 text-sm">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="consent_require_signature" value="1" @checked(old('consent_require_signature', $tool->consent_require_signature)) class="rounded border-tk-line text-tk-accent">
            Require signature on consent screen
        </label>

        <div>
            <label class="text-sm font-medium">Footer on public pages</label>
            <input type="text" name="brand_footer" value="{{ old('brand_footer', $tool->brand_footer) }}" class="w-full rounded-md border border-tk-line px-3 py-2 text-sm" placeholder="Community name · contact email">
        </div>

        <div>
            <label class="text-sm font-medium">Default locale</label>
            <select name="default_locale" class="rounded-md border border-tk-line px-3 py-2 text-sm">
                @foreach (['en' => 'English', 'fr' => 'Français'] as $code => $label)
                    <option value="{{ $code }}" @selected(old('default_locale', $tool->default_locale ?: 'en') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="border-t border-tk-line pt-4">
            <h3 class="font-semibold text-sm mb-2">Scheduled CSV export</h3>
            <p class="text-xs text-tk-muted mb-2">Comma-separated admin emails. Runs daily at 06:00 server time via cron scheduler.</p>
            <input type="text" name="export_recipients" value="{{ old('export_recipients', $scheduledExport->recipient_emails ?? '') }}" class="w-full rounded-md border border-tk-line px-3 py-2 text-sm" placeholder="admin@example.com, analyst@example.com">
            <select name="export_frequency" class="mt-2 rounded-md border border-tk-line px-3 py-2 text-sm">
                @foreach (['weekly' => 'Weekly', 'daily' => 'Daily', 'monthly' => 'Monthly'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('export_frequency', $scheduledExport->frequency ?? 'weekly') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="tk-btn-primary">Save settings</button>
    </form>
</x-app-layout>
