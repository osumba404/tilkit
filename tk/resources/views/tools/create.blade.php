<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Create a collection tool" />
    </x-slot>

    <form method="POST" action="{{ route('tools.store') }}" class="tk-card max-w-2xl p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Tool name</label>
            <input name="name" value="{{ old('name') }}" required class="tk-input" placeholder="Clinical logbook - Year 3">
            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Purpose (DPA Article 5)</label>
            <textarea name="purpose" rows="4" required class="tk-input">{{ old('purpose') }}</textarea>
            @error('purpose')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Lawful basis</label>
            <select name="lawful_basis" class="w-full rounded-md border-tk-line">
                @foreach (['consent','contract','legal_obligation','public_interest','legitimate_interest'] as $basis)
                    <option value="{{ $basis }}" @selected(old('lawful_basis') === $basis)>{{ str_replace('_', ' ', $basis) }}</option>
                @endforeach
            </select>
        </div>
        <p class="text-sm text-tk-muted">You will become the <strong>tool administrator</strong> with full rights to design forms, view analytics, and export data.</p>
        <button type="submit" class="tk-btn-primary">Create tool</button>
    </form>
</x-app-layout>
