<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">New form — {{ $tool->name }}</h2>
    </x-slot>

    <form method="POST" action="{{ route('tools.forms.store', $tool) }}" class="tk-card max-w-2xl p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input name="title" required class="w-full rounded-md border border-tk-line px-3 py-2" value="{{ old('title') }}">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-md border border-tk-line px-3 py-2">{{ old('description') }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="allow_anonymous" value="1" @checked(old('allow_anonymous'))>
            Allow anonymous web submissions
        </label>
        <button class="tk-btn-primary">Continue to builder</button>
    </form>
</x-app-layout>
