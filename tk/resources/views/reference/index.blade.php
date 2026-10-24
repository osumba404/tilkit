@guest
<x-layouts.site title="Field reference - Tilkit">
    <x-page-header
        title="Data collection field reference"
        subtitle="200+ catalogued collection points - searchable master catalogue."
        class="mb-6"
    />
    <div class="tk-card overflow-hidden min-h-[60vh]" style="height: min(80vh, 900px);">
        <iframe src="{{ asset('field-guide/field-reference.html') }}" class="w-full h-full border-0" title="Field reference"></iframe>
    </div>
</x-layouts.site>
@else
<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Data collection field reference"
            subtitle="Master catalogue from the Tilkit field guide (200+ points, 16 families)."
        />
    </x-slot>

    <div class="tk-card overflow-hidden min-h-[60vh]" style="height: min(80vh, 900px);">
        <iframe
            src="{{ asset('field-guide/field-reference.html') }}"
            title="Data collection field reference"
            class="w-full h-full border-0"
        ></iframe>
    </div>
</x-app-layout>
@endguest
